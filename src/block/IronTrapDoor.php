<?php

namespace Faction\block;

class IronTrapDoor extends Durability
{
    public function getDurability(): int
    {
        return 15;
    }
}