<?php

namespace Faction\item;

class Armor extends Durable
{
    public function __construct(private readonly float $maxDurability = 0, private readonly int $defensePoints = 0)
    {
    }

    public function getDefensePoints(): int
    {
        return $this->defensePoints;
    }

    public function getMaxDurability(): int
    {
        return intval($this->maxDurability);
    }
}