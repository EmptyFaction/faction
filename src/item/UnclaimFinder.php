<?php

namespace Faction\item;

use pocketmine\event\player\PlayerItemUseEvent;

class UnclaimFinder extends Durable
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        // Faire un système d'amélioration peut être

        if ($this->inCooldown($player)) {
            $event->cancel();
            return true;
        }

        $this->setCooldown($player, 1);

        $xMax = $player->getPosition()->getX() + 32;
        $zMax = $player->getPosition()->getZ() + 32;

        $percentage = 0;

        for ($x = $player->getPosition()->getX() - 32; $x <= $xMax; $x += 16) {
            for ($z = $player->getPosition()->getZ() - 32; $z <= $zMax; $z += 16) {
                if (!$player->getWorld()->isChunkLoaded($x >> 4, $z >> 4)) {
                    $player->getWorld()->loadChunk($x >> 4, $z >> 4);
                }

                $chunk = $player->getWorld()->getChunk($x >> 4, $z >> 4);
                $percentage += count($chunk->getTiles());
            }
        }

        $player->sendPopup("§l§c» §r§c" . $percentage . "%% §fdétectés §l§c«");
        $this->applyDamage($player);

        $event->cancel();
        return true;
    }

    public function getMaxDurability(): int
    {
        return 800;
    }
}