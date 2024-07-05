<?php /* @noinspection PhpDeprecationInspection */

namespace Faction\item\enchantment;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\VanillaItems;
use pocketmine\lang\Translatable;

class DoublePickaxe extends Enchantment
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
        return "Double Pickaxe";
    }

    public function getRarity(): int
    {
        return Rarity::UNCOMMON;
    }

    public function getPrimaryItemFlags(): int
    {
        return ItemFlags::PICKAXE;
    }

    public function getFrenchName(): string
    {
        return "Pioche double";
    }

    public function getSecondaryItemFlags(): int
    {
        return ItemFlags::NONE;
    }

    public function getMaxLevel(): int
    {
        return 1;
    }

    public function onBreak(BlockBreakEvent $event, EnchantmentInstance $enchantmentInstance): void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        $item = VanillaItems::NETHERITE_PICKAXE();
        $block->getPosition()->getWorld()->useBreakOn($block->getPosition()->add(0, -1, 0), $item, $player, true);
    }
}