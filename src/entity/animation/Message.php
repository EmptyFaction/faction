<?php

namespace Faction\entity\animation;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Message extends Living
{
    protected int $lifeTime = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);
        $this->setLifeTime(50);
    }

    public function setLifeTime(int $life): void
    {
        $this->lifeTime = $life;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::CHICKEN;
    }

    public function getName(): string
    {
        return "Chiken";
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($this->doLifeTimeTick()) {
            $hasUpdate = true;
        }
        return $hasUpdate;
    }

    protected function doLifeTimeTick(): bool
    {
        if (!$this->isFlaggedForDespawn() && --$this->lifeTime < 0) {
            $this->flagForDespawn();
            return true;
        }
        return false;
    }

    public function initEntityB(string $message): void
    {
        $this->setNameTag($message);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.7, 0.4);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setHealth($this->getMaxHealth());
        $this->setNameTagAlwaysVisible();
        $this->setScale(0.001);
        $this->setHasGravity(false);
    }
}