<?php

namespace Faction\item;

use pocketmine\block\Block;
use pocketmine\block\BlockToolType;
use pocketmine\block\Dirt;
use pocketmine\block\Grass;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item as PmItem;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\world\sound\ItemUseOnBlockSound;

class FarmAxe extends Durable
{
    public function __construct(
        private readonly float $maxDurability = 0,
        private readonly int   $baseEfficiency = 0
    )
    {
    }

    public function onBreak(BlockBreakEvent $event): bool
    {
        $block = $event->getBlock();

        $event->setDrops($block->getDrops($this->getCompatibleTool($block)));
        $event->setXpDropAmount($block->getXpDropForTool($this->getCompatibleTool($block)));

        return false;
    }

    private function getCompatibleTool(Block $block): PmItem
    {
        $toolType = $block->getBreakInfo()->getToolType();

        return match ($toolType) {
            BlockToolType::SWORD => VanillaItems::NETHERITE_SWORD(),
            BlockToolType::SHOVEL => VanillaItems::NETHERITE_SHOVEL(),
            BlockToolType::AXE => VanillaItems::NETHERITE_AXE(),
            BlockToolType::HOE => VanillaItems::NETHERITE_HOE(),
            default => VanillaItems::NETHERITE_PICKAXE()
        };
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $position = $block->getPosition();

        if (
            $event->getFace() !== Facing::DOWN && $event->getAction() === $event::RIGHT_CLICK_BLOCK &&
            ($block instanceof Grass || $block instanceof Dirt)
        ) {
            $this->applyDamage($player);
            $newBlock = VanillaBlocks::FARMLAND();

            $position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), new ItemUseOnBlockSound($newBlock));
            $position->getWorld()->setBlock($position, $newBlock);
        }

        return false;
    }

    public function getBaseEfficiency(): int
    {
        return $this->baseEfficiency;
    }

    public function getMaxDurability(): int
    {
        return intval($this->maxDurability);
    }
}