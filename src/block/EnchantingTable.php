<?php

namespace Faction\block;

use Faction\command\player\Enchant;
use Faction\Util;
use pocketmine\event\player\PlayerInteractEvent;

class EnchantingTable extends Durability
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        if (!$player->isSneaking() && $event->getAction() === $event::RIGHT_CLICK_BLOCK) {
            Util::removeCurrentWindow($player);
            Enchant::openEnchantTable($player, false);

            $event->cancel();
            return true;
        }

        return parent::onInteract($event);
    }

    public function getDurability(): int
    {
        return 20;
    }
}