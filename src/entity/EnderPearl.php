<?php

namespace Faction\entity;

use pocketmine\block\Block;
use pocketmine\block\PressurePlate;
use pocketmine\block\Tripwire;
use pocketmine\entity\projectile\EnderPearl as PmEnderPearl;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;

class EnderPearl extends PmEnderPearl
{
    protected function onHit(ProjectileHitEvent $event): void
    {
        $owner = $this->getOwningEntity();

        if ($owner !== null) {
            if ($owner->getWorld() !== $this->getWorld()) {
                return;
            }

            parent::onHit($event);
        }
    }

    protected function calculateInterceptWithBlock(Block $block, Vector3 $start, Vector3 $end): ?RayTraceResult
    {
        if ($block instanceof PressurePlate || $block instanceof Tripwire) {
            $position = $block->getPosition();
            $bb = new AxisAlignedBB($position->getX(), $position->getY(), $position->getZ(), $position->getX(), $position->getY(), $position->getZ());

            return new RayTraceResult($bb, Facing::UP, $block->getPosition());
        } else {
            return $block->calculateIntercept($start, $end);
        }
    }
}