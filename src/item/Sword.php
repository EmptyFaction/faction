<?php

namespace Faction\item;

class Sword extends Durable
{
    public function __construct(private readonly float $maxDurability = -1, private readonly int $attackPoints = -1)
    {
    }

    public function getAttackPoints(): int
    {
        return $this->attackPoints;
    }

    public function getMaxDurability(): int
    {
        return intval($this->maxDurability);
    }
}