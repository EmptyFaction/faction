<?php /* @noinspection PhpDeprecationInspection */

namespace Faction\item\enchantment;

use Faction\Session;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\lang\Translatable;

class Tokenator extends Enchantment
{
    public function __construct()
    {
        parent::__construct(
            $this->getName(),
            $this->getRarity(),
            $this->getPrimaryItemFlags(),
            $this->getSecondaryItemFlags(),
            $this->getMaxLevel(),
        );
    }

    public function getName(): Translatable|string
    {
        return "Tokenator";
    }

    public function getRarity(): int
    {
        return Rarity::UNCOMMON;
    }

    public function getPrimaryItemFlags(): int
    {
        return ItemFlags::PICKAXE;
    }

    public function getSecondaryItemFlags(): int
    {
        return ItemFlags::NONE;
    }

    public function getFrenchName(): string
    {
        return "Tokenator";
    }

    public function getMaxLevel(): int
    {
        return 3;
    }

    public function onBreak(BlockBreakEvent $event, EnchantmentInstance $enchantmentInstance): void
    {
        $player = $event->getPlayer();
        $level = $enchantmentInstance->getLevel();

        Session::get($player)->addValue("money", $level);
    }
}