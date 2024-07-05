<?php

namespace Faction\item\enchantment;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\Enchantment as PmEnchantment;
use pocketmine\item\enchantment\EnchantmentInstance;

class Enchantment extends PmEnchantment
{
    public function onBreak(BlockBreakEvent $event, EnchantmentInstance $enchantmentInstance): void
    {
    }

    public function getFrenchName(): string
    {
        return "Inconnu";
    }
}