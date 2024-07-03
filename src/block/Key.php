<?php

namespace Faction\block;

use pocketmine\event\block\BlockPlaceEvent;

class Key extends Block
{
    public function onPlace(BlockPlaceEvent $event): bool
    {
        $event->cancel();
        return true;
    }
}