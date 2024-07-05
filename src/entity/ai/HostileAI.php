<?php

namespace Faction\entity\ai;

use Faction\handler\Faction;
use Faction\Util;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\Position;

abstract class HostileAI extends PassiveAI
{
    public int $attackWait = 10;
    public int $ticks = 0;

    private ?Player $target = null;
    private Position $lastPosition;

    private int $findNewTargetTicks = 0;
    private int $missedHit = 0;

    abstract public function getLegacyId(): int;

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $this->ticks++;

        if (
            (!$this->canAccessSpawn() && Util::insideZone($this->getPosition(), "spawn")) ||
            (!$this->canAccesClaim() && Faction::inClaim($this->getPosition()->x, $this->getPosition()->z)[0])
        ) {
            $this->teleportLastPosition();
        }

        $this->lastPosition = $this->getPosition();
        parent::entityBaseTick($tickDiff);

        if (!$this->isAlive()) {
            if (!$this->closed) {
                $this->flagForDespawn();
            }
            return false;
        }

        if ($this->canAttack()) {
            if ($this->target instanceof Player) {
                return $this->attackTarget();
            }

            if ($this->autoSearchTarget()) {
                if ($this->findNewTargetTicks > 0) {
                    $this->findNewTargetTicks--;
                } else if ($this->findNewTargetTicks === 0) {
                    $this->findNewTarget();
                }
            }
        }

        $this->liveDreamLife();
        return $this->isAlive();
    }

    abstract public function canAccessSpawn(): bool;

    abstract public function canAccesClaim(): bool;

    public function teleportLastPosition(): void
    {
        $this->setPosition($this->lastPosition ?? $this->getPosition());
        $this->updateMovement();
    }

    abstract public function canAttack(): bool;

    public function attackTarget(): bool
    {
        $target = $this->target;

        if (!$this->checkTarget($target)) {
            $this->target = null;
            $this->missedHit = 0;

            return true;
        }

        $this->moveEntity($target->getPosition());

        if ($this->getPosition()->distance($target->getPosition()) <= $this->getReach() && $this->attackWait <= 0) {
            $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());

            $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage(), [], $this->hitKnockback());
            $target->attack($ev);

            $this->attackWait = $this->getAttackWaiting();
            $this->missedHit = 0;
        } else {
            $this->missedHit++;
        }

        $this->attackWait--;
        return $this->isAlive();
    }

    private function checkTarget(?Player $target): bool
    {
        return
            $target instanceof Player &&
            $target->isAlive() &&
            !$target->isClosed() &&
            $target->isConnected() &&
            ($this->canAccesClaim() ? !$target->isCreative() : $target->getGamemode() === GameMode::SURVIVAL()) &&
            $this->getMaxDistancePlayer() > $target->getPosition()->distance($this->getPosition()) &&
            ($this->canAccessSpawn() || !Util::insideZone($target->getPosition(), "spawn")) &&
            ($this->canAccesClaim() || !Faction::inClaim($target->getPosition()->x, $this->getPosition()->z)[0]) &&
            $this->missedHitStop() > $this->missedHit;
    }

    abstract public function getMaxDistancePlayer(): int;

    abstract public function missedHitStop(): int;

    abstract public function getReach(): int;

    abstract public function getDamage(): int;

    abstract public function hitKnockback(): float;

    public function attack(EntityDamageEvent $source): void
    {
        $this->updateNameTag();

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player) {
                if (is_null($this->target) && $this->checkTarget($damager)) {
                    $this->target = $damager;
                    $this->missedHit = 0;

                    $this->knockBack($this->motion->x, $this->motion->z, $this->getKnockback());
                }
            }
        }

        parent::attack($source);
    }

    private function updateNameTag(): void
    {
        if (!is_null($this->getNameTagUpdate())) {
            $this->setNameTag($this->getNameTagUpdate());
        }

        if (!is_null($this->getScoreTagUpdate())) {
            $this->setScoreTag($this->getScoreTagUpdate());
        }

        $this->setNameTagAlwaysVisible();
    }

    abstract public function getNameTagUpdate(): ?string;

    abstract public function getScoreTagUpdate(): ?string;

    abstract public function getKnockback(): float;

    abstract protected function getAttackWaiting(): int;

    abstract public function autoSearchTarget(): bool;

    public function findNewTarget(): void
    {
        $distance = $this->searchTargetMaxDistance();
        $target = null;

        foreach ($this->getWorld()->getPlayers() as $player) {
            if ($player instanceof Player && $distance >= $player->getPosition()->distance($this->getPosition()) && $this->checkTarget($player)) {
                $distance = $player->getPosition()->distance($this->getPosition());
                $target = $player;
            }
        }

        $this->findNewTargetTicks = $this->searchTargetTicks();
        $this->target = (!is_null($target) ? $target : null);

        $this->missedHit = 0;
    }

    abstract public function searchTargetMaxDistance(): ?int;

    abstract public function searchTargetTicks(): ?int;

    abstract public function getSpeed(): float;

    abstract public function reducePitch(): int;

    protected function initEntity(CompoundTag $nbt): void
    {
        $this->updateNameTag();

        $this->setHealth($this->getMaxHealth());
        $this->setScale($this->getBaseSize());

        parent::initEntity($nbt);
    }

    abstract public function getBaseSize(): float;
}