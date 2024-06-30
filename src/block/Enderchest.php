<?php

namespace Faction\block;

class Enderchest extends Durability
{
    public function getDurability(): int
    {
        return 15;
    }
}