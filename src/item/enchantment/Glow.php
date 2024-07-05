<?php /* @noinspection PhpDeprecationInspection */

namespace Faction\item\enchantment;

use pocketmine\item\enchantment\ItemFlags;
use pocketmine\lang\Translatable;

class Glow extends Enchantment
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
        return "glow";
    }

    public function getRarity(): int
    {
        return -1;
    }

    public function getPrimaryItemFlags(): int
    {
        return ItemFlags::ALL;
    }

    public function getSecondaryItemFlags(): int
    {
        return ItemFlags::NONE;
    }

    public function getMaxLevel(): int
    {
        return 1;
    }
}