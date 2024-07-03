<?php

namespace Faction\block;

use Faction\item\ExtraVanillaItems;
use Faction\item\FarmAxe;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Axe;

class MelonStem extends Block
{
    public function onBreak(BlockBreakEvent $event): bool
    {
        $item = $event->getItem();

        if ($item instanceof Axe || ExtraVanillaItems::getItem($item) instanceof FarmAxe) {
            $event->cancel();
            return true;
        }

        return false;
    }
}