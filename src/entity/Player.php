<?php

namespace Faction\entity;

use Faction\item\Armor;
use Faction\item\ExtraVanillaItems;
use Faction\item\FarmAxe;
use Faction\item\Sword;
use Faction\Main;
use Faction\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\animation\CriticalHitAnimation;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\MeleeWeaponEnchantment;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player as PmPlayer;
use pocketmine\ServerProperties;
use pocketmine\world\sound\EntityAttackNoDamageSound;
use pocketmine\world\sound\EntityAttackSound;
use pocketmine\world\sound\FireExtinguishSound;
use pocketmine\world\sound\ItemBreakSound;

class Player extends PmPlayer
{
    protected ?SurvivalBlockHandler $blockBreakHandlerCustom = null;

    private ?Item $actualHandItem = null;
    private ?Item $actualOffHandItem = null;

    public function attackEntity(Entity $entity): bool
    {
        if (!$entity->isAlive()) {
            return false;
        }
        if ($entity instanceof ItemEntity || $entity instanceof Arrow) {
            $this->logger->debug("Attempted to attack non-attackable entity " . get_class($entity));
            return false;
        }

        $heldItem = $this->inventory->getItemInHand();
        $oldItem = clone $heldItem;

        $ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getHeldItemAttackPoints($heldItem));
        if (!$this->canInteract($entity->getLocation(), 8)) {
            $this->logger->debug("Cancelled attack of entity " . $entity->getId() . " due to not currently being interactable");
            $ev->cancel();
        } elseif ($this->isSpectator() || ($entity instanceof self && !$this->server->getConfigGroup()->getConfigBool(ServerProperties::PVP))) {
            $ev->cancel();
        }

        $meleeEnchantmentDamage = 0;
        /** @var EnchantmentInstance[] $meleeEnchantments */
        $meleeEnchantments = [];
        foreach ($heldItem->getEnchantments() as $enchantment) {
            $type = $enchantment->getType();
            if ($type instanceof MeleeWeaponEnchantment && $type->isApplicableTo($entity)) {
                $meleeEnchantmentDamage += $type->getDamageBonus($enchantment->getLevel());
                $meleeEnchantments[] = $enchantment;
            }
        }
        $ev->setModifier($meleeEnchantmentDamage, EntityDamageEvent::MODIFIER_WEAPON_ENCHANTMENTS);

        if (!$this->isSprinting() && !$this->isFlying() && $this->fallDistance > 0 && !$this->effectManager->has(VanillaEffects::BLINDNESS()) && !$this->isUnderwater()) {
            $ev->setModifier($ev->getFinalDamage() / 2, EntityDamageEvent::MODIFIER_CRITICAL);
        }

        $entity->attack($ev);
        $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());

        $soundPos = $entity->getPosition()->add(0, $entity->size->getHeight() / 2, 0);
        if ($ev->isCancelled()) {
            $this->getWorld()->addSound($soundPos, new EntityAttackNoDamageSound());
            return false;
        }
        $this->getWorld()->addSound($soundPos, new EntityAttackSound());

        if ($ev->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0 && $entity instanceof Living) {
            $entity->broadcastAnimation(new CriticalHitAnimation($entity));
        }

        foreach ($meleeEnchantments as $enchantment) {
            $type = $enchantment->getType();
            assert($type instanceof MeleeWeaponEnchantment);
            $type->onPostAttack($this, $entity, $enchantment->getLevel());
        }

        if ($this->isAlive()) {
            //reactive damage like thorns might cause us to be killed by attacking another mob, which
            //would mean we'd already have dropped the inventory by the time we reached here
            $returnedItems = [];
            $heldItem->onAttackEntity($entity, $returnedItems);
            $this->returnItemsFromAction($oldItem, $heldItem, $returnedItems);

            $this->hungerManager->exhaust(0.1, PlayerExhaustEvent::CAUSE_ATTACK);
        }

        return true;
    }

    private function getHeldItemAttackPoints(Item $heldItem): int
    {
        $item = ExtraVanillaItems::getItem($heldItem);

        if ($item instanceof Sword && $item->getAttackPoints() > 0) {
            return $item->getAttackPoints();
        } else if ($item instanceof FarmAxe) {
            return $heldItem->getAttackPoints() - 2;
        } else {
            return $heldItem->getAttackPoints();
        }
    }

    public function attack(EntityDamageEvent $source): void
    {
        Util::updateNametag($this);
        parent::attack($source);
    }

    private function returnItemsFromAction(Item $oldHeldItem, Item $newHeldItem, array $extraReturnedItems): void
    {
        $heldItemChanged = false;

        if (!$newHeldItem->equalsExact($oldHeldItem) && $oldHeldItem->equalsExact($this->inventory->getItemInHand())) {
            //determine if the item was changed in some meaningful way, or just damaged/changed count
            //if it was really changed we always need to set it, whether we have finite resources or not
            $newReplica = clone $oldHeldItem;
            $newReplica->setCount($newHeldItem->getCount());
            if ($newReplica instanceof Durable && $newHeldItem instanceof Durable) {
                $newReplica->setDamage($newHeldItem->getDamage());
            }
            $damagedOrDeducted = $newReplica->equalsExact($newHeldItem);

            if (!$damagedOrDeducted || $this->hasFiniteResources()) {
                if ($newHeldItem instanceof Durable && $newHeldItem->isBroken()) {
                    $this->broadcastSound(new ItemBreakSound());
                }
                $this->inventory->setItemInHand($newHeldItem);
                $heldItemChanged = true;
            }
        }

        if (!$heldItemChanged) {
            $newHeldItem = $oldHeldItem;
        }

        if ($heldItemChanged && count($extraReturnedItems) > 0 && $newHeldItem->isNull()) {
            $this->inventory->setItemInHand(array_shift($extraReturnedItems));
        }
        foreach ($this->inventory->addItem(...$extraReturnedItems) as $drop) {
            $ev = new PlayerDropItemEvent($this, $drop);

            if ($this->isSpectator()) {
                $ev->cancel();
            }

            $ev->call();

            if (!$ev->isCancelled()) {
                $this->dropItem($drop);
            }
        }
    }

    public function onUpdate(int $currentTick): bool
    {
        $this->blockBreakHandlerCustom?->update() ?: $this->blockBreakHandlerCustom = null;
        return parent::onUpdate($currentTick);
    }

    public function attackBlock(Vector3 $pos, int $face): bool
    {
        if ($pos->distanceSquared($this->location) > 10000) {
            return false;
        }
        $target = $this->getWorld()->getBlock($pos);

        $ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, null, $face, PlayerInteractEvent::LEFT_CLICK_BLOCK);

        if ($this->isSpectator()) {
            $ev->cancel();
        }

        $ev->call();
        if ($ev->isCancelled()) {
            return false;
        }
        $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
        if ($target->onAttack($this->inventory->getItemInHand(), $face, $this)) {
            return true;
        }
        $block = $target->getSide($face);
        if ($block->getTypeId() === VanillaBlocks::FIRE()->getTypeId()) {
            $this->getWorld()->setBlock($block->getPosition(), VanillaBlocks::AIR());
            $this->getWorld()->addSound($block->getPosition()->add(0.5, 0.5, 0.5), new FireExtinguishSound());
            return true;
        }


        if (!$this->isCreative() && !$block->getBreakInfo()->breaksInstantly()) {
            $this->blockBreakHandlerCustom = new SurvivalBlockHandler($this, $pos, $target, $face, 16);
        }

        return true;
    }

    public function continueBreakBlock(Vector3 $pos, int $face): void
    {
        if ($this->blockBreakHandlerCustom !== null && $this->blockBreakHandlerCustom->getBlockPos()->distanceSquared($pos) < 0.0001) {
            $this->blockBreakHandlerCustom->setTargetedFace($face);
            $this->blockBreakHandlerCustom->setTargetedFace($face);
            if (($this->blockBreakHandlerCustom->getBreakProgress() + $this->blockBreakHandlerCustom->getBreakSpeed()) >= 0.80) {
                $pos = $this->blockBreakHandlerCustom->getBlockPos();
                $this->breakBlock($pos);
            }
        }
    }

    public function breakBlock(Vector3 $pos): bool
    {
        $this->removeCurrentWindow();
        if ($this->canInteract($pos->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 7)) {
            $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
            $this->stopBreakBlock($pos);
            $item = $this->inventory->getItemInHand();
            $oldItem = clone $item;
            if ($this->getWorld()->useBreakOn($pos, $item, $this, true)) {
                if ($this->hasFiniteResources() && !$item->equalsExact($oldItem) && $oldItem->equalsExact($this->inventory->getItemInHand())) {
                    if ($item instanceof Durable && $item->isBroken()) {
                        $this->broadcastSound(new ItemBreakSound());
                    }
                    $this->inventory->setItemInHand($item);
                }
                $this->hungerManager->exhaust(0.005, PlayerExhaustEvent::CAUSE_MINING);
                return true;
            }
        } else {
            $this->logger->debug("Cancelled block break at $pos due to not currently being interactable");
        }

        return false;
    }

    public function stopBreakBlock(Vector3 $pos): void
    {
        if ($this->blockBreakHandlerCustom !== null && $this->blockBreakHandlerCustom->getBlockPos()->distanceSquared($pos) < 0.0001) {
            $this->blockBreakHandlerCustom = null;
        }
    }

    public function getArmorPoints(): int
    {
        $total = 0;

        foreach ($this->armorInventory->getContents() as $itemArmor) {
            $item = ExtraVanillaItems::getItem($itemArmor);

            if ($item instanceof Armor && $item->getDefensePoints() > 0) {
                $total += $item->getDefensePoints();
            } else {
                $total += $itemArmor->getDefensePoints();
            }
        }
        return $total;
    }

    public function heal(EntityRegainHealthEvent $source): void
    {
        Util::updateNametag($this);
        parent::heal($source);
    }

    protected function destroyCycles(): void
    {
        parent::destroyCycles();
        $this->blockBreakHandlerCustom = null;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $tick = parent::entityBaseTick($tickDiff);
        $gamemode = $this->getGamemode();

        if ($gamemode === GameMode::CREATIVE() && !$this->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $this->setGamemode(GameMode::SURVIVAL());
        }

        $this->getHungerManager()->setFood(18);

        if ($this->ticksLived % 5 == 0) {
            $x = $this->getPosition()->getX();
            $z = $this->getPosition()->getZ();

            if (
                (abs($x) > ($border = 10000) || abs($z) > $border) &&
                $this->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()
            ) {
                $spawn = $this->getWorld()->getSpawnLocation();

                $motionX = $spawn->getX() - $x;
                $motionZ = $spawn->getZ() - $z;

                $motionX = (abs($motionX) > 5) ? ($motionX <=> 0) * 5 : $motionX;

                $dist = sqrt(pow($x - $spawn->getX(), 2) + pow($z - $spawn->getZ(), 2));
                $knockBackIntensity = min((mt_rand(10, 15) / 10), $dist / 10);

                $this->knockBack($motionX, $motionZ, $knockBackIntensity);
                $this->sendPopup(Util::ARROW . "Vous avez dépassé la bordure" . Util::IARROW);
            }

            $oldItem = $this->actualHandItem;
            $handItem = $this->getInventory()->getItemInHand();

            if ($oldItem instanceof Item && !$oldItem->equals($handItem, false)) {
                $this->actualHandItem = $handItem;

                ExtraVanillaItems::getItem($oldItem)->oldHoldItem($this);
                ExtraVanillaItems::getItem($handItem)->newHoldItem($this);
            } else if (!$oldItem instanceof Item) {
                $this->actualHandItem = $handItem;
            }

            $oldOffItem = $this->actualOffHandItem;
            $offHandItem = $this->getOffHandInventory()->getItem(0);

            if ($oldOffItem instanceof Item && !$oldOffItem->equals($offHandItem, false)) {
                $this->actualOffHandItem = $offHandItem;

                ExtraVanillaItems::getItem($oldOffItem)->oldHoldOffItem($this);
                ExtraVanillaItems::getItem($offHandItem)->newHoldOffItem($this);
            } else if (!$oldOffItem instanceof Item) {
                $this->actualOffHandItem = $offHandItem;
            }
        }

        return $tick;
    }
}