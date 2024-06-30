<?php

namespace Faction\item;

use pocketmine\entity\effect\Effect;

class TurtleArmor extends Armor
{
    public function __construct(float $maxDurability, int $defensePoints, private readonly Effect $effect, private readonly int $amplifier)
    {
        parent::__construct($maxDurability, $defensePoints);
    }

    public function getEffects(): array
    {
        return [[$this->effect, $this->amplifier]];
    }
}