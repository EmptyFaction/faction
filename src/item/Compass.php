<?php

namespace Faction\item;

use Faction\handler\Faction;
use Faction\Util;
use pocketmine\event\player\PlayerItemUseEvent;

class Compass extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();

        if ($player->isCreative()) {
            return true;
        }

        $x = number_format($player->getPosition()->getX(), 2);
        $y = $player->getPosition()->getFloorY();
        $z = number_format($player->getPosition()->getZ(), 2);

        // Thanks ayzrix
        $degrees = $player->getLocation()->getYaw();
        $direction = Faction::getDirectionsByDegrees($degrees);

        $player->sendPopup(Util::ARROW . "X: §c" . $x . " §fY: §c" . $y . " §fZ: §c" . $z . " §f" . $direction . Util::IARROW);

        return true;
    }
}