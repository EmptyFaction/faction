<?php

namespace Faction\item;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\math\VoxelRayTrace;
use pocketmine\player\Player;

class BoostedShovel extends Durable
{
    const ENCHANT_TAG = "boosted_shovel_lvl";

    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();

        $targets = $this->getLineOfBlocks($player, 3 + $this->getLevel($player));
        array_shift($targets);
        $targets = array_reverse($targets);

        // Check si t en combat ou dernierement en combat

        foreach ($targets as $target) {
            if ($target instanceof Block && $target->hasSameTypeId(VanillaBlocks::AIR())) {
                $player->teleport($target->getPosition()->add(0.5, 0, 0.5));
                $this->applyDamage($player);
                break;
            }
        }

        return true;
    }

    public function getLineOfBlocks(Player $player, int $maxDistance): array
    {
        if ($maxDistance > 120) {
            $maxDistance = 120;
        }

        $blocks = [];
        $nextIndex = 0;

        foreach (VoxelRayTrace::inDirection($player->getLocation()->add(0, $player->getSize()->getEyeHeight(), 0), $player->getDirectionVector(), $maxDistance) as $vector3) {
            if ($nextIndex > $maxDistance - 1) {
                break;
            }

            $block = $player->getWorld()->getBlockAt($vector3->x, $vector3->y, $vector3->z);
            $blocks[$nextIndex++] = $block;
        }

        return $blocks;
    }

    private function getLevel(Player $player): int
    {
        $item = $player->getInventory()->getItemInHand();

        if (is_null($item->getNamedTag()->getTag(self::ENCHANT_TAG))) {
            $item->getNamedTag()->setInt(self::DAMAGE_TAG, 1);
        }

        return $item->getNamedTag()->getInt(self::ENCHANT_TAG, 1);
    }

    public function getMaxDurability(): int
    {
        return 2260;
    }
}