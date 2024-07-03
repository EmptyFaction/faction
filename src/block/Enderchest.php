<?php

namespace Faction\block;

use Faction\handler\Box;
use Faction\handler\trait\CooldownTrait;
use pocketmine\event\player\PlayerInteractEvent;

class Enderchest extends Durability
{
    public function getDurability(): int
    {
        return 15;
    }
}