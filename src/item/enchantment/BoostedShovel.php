<?php /* @noinspection PhpDeprecationInspection */

namespace Faction\item\enchantment;

use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\lang\Translatable;

class BoostedShovel extends Enchantment
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
        return "Boosted Shovel";
    }

    public function getFrenchName(): string
    {
        return "Pelle boostée";
    }

    public function getRarity(): int
    {
        return Rarity::UNCOMMON;
    }

    public function getPrimaryItemFlags(): int
    {
        return ItemFlags::SHOVEL;
    }

    public function getSecondaryItemFlags(): int
    {
        return ItemFlags::NONE;
    }

    public function getMaxLevel(): int
    {
        return 3;
    }
}