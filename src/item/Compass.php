<?php

namespace Faction\item;

use Faction\handler\Cache;
use Faction\Util;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\player\Player;

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

        $cardinal = $this->getCardinalDirection($player);
        $player->sendPopup(Util::ARROW . "X: §c" . $x . " §fY: §c" . $y . " §fZ: §c" . $z . " §f" . $cardinal . Util::IARROW);

        return true;
    }

    public function getCardinalDirection(Player $player): string
    {
        $direction = (floor($player->getLocation()->getYaw()) - 90) % 360;
        if ($direction < 0) $direction += 360;

        foreach (Cache::$config["directions"] as $dir) {
            if ($dir["min"] <= $direction && $direction < $dir["max"]) {
                return $dir["direction"];
            }
        }

        return "O";
    }
}