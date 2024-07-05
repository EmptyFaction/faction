<?php

namespace Faction\entity\ai;

use pocketmine\block\Block;
use pocketmine\block\Flowable;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Facing;
use pocketmine\world\Position;

abstract class PassiveAI extends Living
{
    private array $pitchs = [];

    private int $jumpTicks = 10;
    private int $panicTicks = 0;

    public function setPanic(int $ticks): void
    {
        $this->panicTicks = $ticks;
    }

    public function liveDreamLife(): void
    {
        if ($this->panicTicks > 0) {
            $this->panicTicks--;

            $vector = $this->getPosition()->add(mt_rand(-2, 2), 0, mt_rand(-2, 2));
            $position = new Position($vector->x, $vector->y, $vector->z, $this->getWorld());

            $this->moveEntity($position, true);
        }
    }

    public function moveEntity(Position $direction, bool $passive = false): void
    {
        if ($this->jumpTicks > 0) {
            $this->jumpTicks--;
        }

        if ($this->panicTicks > 0) {
            $this->panicTicks--;
            $speed = $this->getSpeed() * 4;
        } else {
            $speed = $this->getSpeed();
        }

        if ($this->shouldJump()) {
            $this->jump();
        }

        $this->blockInFrontOfEntity();

        if (!$this->isOnGround()) {
            if ($this->motion->y > -$this->gravity * 4) {
                $this->motion->y = -$this->gravity * 4;
            } else {
                $this->motion->y += $this->isUnderwater() ? $this->gravity : -$this->gravity;
            }
        } else {
            $this->motion->y -= $this->gravity;
        }

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);

        $x = $direction->x - $this->getPosition()->x;
        $y = $direction->y - $this->getPosition()->y;
        $z = $direction->z - $this->getPosition()->z;

        if ($x * $x + $z * $z < 1.2) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = ($this->isUnderwater() ? $speed / 2 : $speed) * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = ($this->isUnderwater() ? $speed / 2 : $speed) * 0.15 * ($z / (abs($x) + abs($z)));
        }

        if (count($this->pitchs) >= 20) {
            array_shift($this->pitchs);
        }

        $pitch = $passive ? 0 : rad2deg(atan(-$y)) - $this->reducePitch();
        $this->pitchs[] = $pitch;

        $this->location->yaw = rad2deg(atan2(-$x, $z));
        $this->location->pitch = (array_sum($this->pitchs) / count($this->pitchs));

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        $this->updateMovement();
    }

    public function shouldJump(): bool
    {
        if ($this->jumpTicks > 0) {
            return false;
        } else if (!$this->isOnGround()) {
            return false;
        }

        return $this->isCollidedHorizontally ||
            ($this->getFrontBlock()->getId() != 0 || $this->getFrontBlock(-1) instanceof Stair) ||
            ($this->getWorld()->getBlock($this->getPosition()->asVector3()->add(0, -0, 5)) instanceof Slab &&
                (!$this->getFrontBlock(-0.5) instanceof Slab and $this->getFrontBlock(-0.5)->getId() != 0)) &&
            $this->getFrontBlock(1)->getId() == 0 &&
            $this->getFrontBlock(2)->getId() == 0 &&
            !$this->getFrontBlock() instanceof Flowable &&
            $this->jumpTicks == 0;
    }

    public function getFrontBlock($y = 0): Block
    {
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->asVector3()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();

        return $this->getWorld()->getBlock($pos);
    }

    public function jump(): void
    {
        if ($this->jumpTicks > 0) {
            return;
        }

        $this->motion->y = $this->gravity * 12;
        $this->jumpTicks = 30;
    }

    private function blockInFrontOfEntity(): void
    {
        $dv = $this->getDirectionVector();
        $pos = $this->getPosition()->asVector3()->add($dv->x * $this->getScale(), 1, $dv->z * $this->getScale())->round();

        $block = $this->getWorld()->getBlock($pos);

        if ($block->isSolid() && !$block instanceof Flowable) {
            $up = $block->getSide(Facing::UP);

            if ($up instanceof Flowable || $up->getId() == BlockLegacyIds::AIR || !$up->isSolid()) {
                $this->motion->y = $this->gravity * 12;
            }
        }
    }

    public function attack(EntityDamageEvent $source): void
    {
        $cause = $source->getCause();

        if ($cause === $source::CAUSE_FALL) {
            $source->cancel();
            return;
        }
        parent::attack($source);
    }
}