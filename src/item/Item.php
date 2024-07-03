<?php

/**
 *
 * @noinspection PhpUnused
 * @noinspection PhpUnusedParameterInspection
 *
 */

namespace Faction\item;

use Faction\handler\trait\CooldownTrait;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\inventory\ItemDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Durable as PmDurable;
use pocketmine\item\Item as PmItem;
use pocketmine\item\Releasable;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\sound\ItemBreakSound;

class Item
{
    use CooldownTrait;

    // False = no return in the event
    // True = return in the event
    // Cancel in the function not automatic

    public function onUse(PlayerItemUseEvent $event): bool
    {
        return false;
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        return false;
    }

    public function newHoldItem(Player $player): void
    {
    }

    public function oldHoldItem(Player $player): void
    {
    }

    public function newHoldOffItem(Player $player): void
    {
    }

    public function oldHoldOffItem(Player $player): void
    {
    }

    public function onDamage(ItemDamageEvent $event): bool
    {
        return false;
    }

    public function onHeld(PlayerItemHeldEvent $event): bool
    {
        return false;
    }

    public function onBreak(BlockBreakEvent $event): bool
    {
        return false;
    }

    public function onConsume(PlayerItemConsumeEvent $event): bool
    {
        return false;
    }

    public function applyDamage(Player $player, float $damage = 1): void
    {
        $item = $player->getInventory()->getItemInHand();

        if ($item instanceof PmDurable) {
            $item->applyDamage($damage);

            if ($item->isBroken()) {
                $this->destroy($player);
            } else {
                $player->getInventory()->setItemInHand($item);
            }
        }
    }

    private function destroy(Player $player): void
    {
        $player->getInventory()->setItemInHand(VanillaItems::AIR());
        $player->broadcastSound(new ItemBreakSound());
    }

    public function addEffects(ArmorInventory $inventory, PmItem $item): void
    {
        foreach ($this->getEffects($item) as $data) {
            [$effect, $amplifier] = $data;
            $inventory->getHolder()->getEffects()->add(new EffectInstance($effect, 20 * 60 * 60 * 24, $amplifier, false));
        }
    }

    public function getEffects(PmItem $item): array
    {
        return [];
    }

    public function removeEffects(ArmorInventory $inventory, PmItem $item): void
    {
        foreach ($this->getEffects($item) as $data) {
            [$effect,] = $data;
            $inventory->getHolder()->getEffects()->remove($effect);
        }
    }

    public function projectileSucces(Player $player, PmItem $item, bool $pop = true): void
    {
        $player->resetItemCooldown($item);
        $player->setUsingItem($item instanceof Releasable && $item->canStartUsingItem($player));

        if ($player->getGamemode() !== GameMode::CREATIVE() && $pop) {
            $newItem = $item->pop()->isNull() ? VanillaItems::AIR() : $item;
            $player->getInventory()->setItemInHand($newItem);
        }
    }
}