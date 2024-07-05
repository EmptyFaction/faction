<?php

namespace Faction\entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\FlintSteel;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\sound\IgniteSound;

class Creeper extends Living
{
    private int $time;

    private bool $explode = false;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CREEPER;
    }

    public function getName(): string
    {
        return "Creeper";
    }

    public function getDrops(): array
    {
        return [];
    }

    public function onUpdate(int $currentTick): bool
    {
        if ($this->explode) {
            $time = $this->time;
            $seconds = intval(abs($time / 20));

            $ms = 5 * round($time - ($seconds * 20));
            $ms = 10 > $ms ? "0" . $ms : $ms;

            $nametag = "§cExplosion in " . $seconds . "." . $ms . "s";

            $this->setNameTag($nametag);
            $this->time--;

            if (0 >= $this->time) {
                $this->explode();
            }
        }
        return parent::onUpdate($currentTick);
    }

    private function explode(): void
    {
        $ev = new EntityPreExplodeEvent($this, 3);
        $ev->call();

        if (!$ev->isCancelled()) {
            $explosion = new Explosion(Position::fromObject($this->location->add(0, $this->size->getHeight() / 2, 0), $this->getWorld()), $ev->getRadius(), $this);

            if ($ev->isBlockBreaking()) {
                $explosion->explodeA();
            }

            $explosion->explodeB();
        }

        $this->close();
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() === $source::CAUSE_ENTITY_EXPLOSION) {
            $source->cancel();
        }
    }

    public function onInteract(Player $player, Vector3 $clickPos): bool
    {
        $item = $player->getInventory()->getItemInHand();

        if (($item instanceof FlintSteel || $item->hasEnchantment(VanillaEnchantments::FIRE_ASPECT())) && !$this->explode) {
            if ($item instanceof Durable) {
                $item->applyDamage(1);
                $player->getInventory()->setItemInHand($item);
            }

            $this->makeExplode();
            return true;
        }
        return false;
    }

    private function makeExplode(float $time = 3.00): void
    {
        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::POWERED, true);
        $this->broadcastSound(new IgniteSound());

        $this->explode = true;
        $this->time = max(0, $time) * 20;
    }

    protected function initEntity(CompoundTag $nbt, float $time = 3): void
    {
        parent::initEntity($nbt);
        $this->makeExplode($time);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(1.8, 0.6);
    }
}