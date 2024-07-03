<?php

namespace Faction\entity\animation;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\object\ItemEntity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\timings\Timings;

class Box extends ItemEntity
{
    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.30, 0.30);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setNameTagAlwaysVisible();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }

        Timings::$itemEntityBaseTick->startTiming();

        try {
            return Entity::entityBaseTick($tickDiff);
        } finally {
            Timings::$itemEntityBaseTick->stopTiming();
        }
    }

    public function onCollideWithPlayer(Player $player): void
    {
    }
}