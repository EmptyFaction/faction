<?php

namespace Faction\block;

class IronDoor extends Durability
{
    public function getDurability(): int
    {
        return 15;
    }
}