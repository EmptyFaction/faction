<?php

namespace Faction\item;

use pocketmine\block\MobHead;
use pocketmine\block\utils\MobHeadType;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Item as PmItem;

class EffectHead extends Item
{
    public function getEffects(PmItem $item): array
    {
        $itemBlock = $item->getBlock();

        if ($itemBlock instanceof MobHead) {
            /** @noinspection PhpUncoveredEnumCasesInspection */
            switch ($itemBlock->getMobHeadType()) {
                case MobHeadType::ZOMBIE:
                    return [
                        [VanillaEffects::HASTE(), 1],
                        [VanillaEffects::NIGHT_VISION(), 0]
                    ];
                case MobHeadType::CREEPER:
                    return [
                        [VanillaEffects::SPEED(), 2],
                        [VanillaEffects::NIGHT_VISION(), 0]
                    ];
                case MobHeadType::DRAGON:
                    return [
                        [VanillaEffects::INVISIBILITY(), 0],
                        [VanillaEffects::NIGHT_VISION(), 0]
                    ];
                case MobHeadType::WITHER_SKELETON:
                case MobHeadType::PIGLIN:
                    return [
                        [VanillaEffects::NIGHT_VISION(), 0]
                    ];
            }
        }

        return [];
    }

    public function removeEffects(ArmorInventory $inventory, PmItem $item): void
    {
        $itemBlock = $item->getBlock();

        if ($itemBlock instanceof MobHead && $itemBlock->getMobHeadType() === MobHeadType::DRAGON) {
            $inventory->getHolder()->setNameTagAlwaysVisible();
        }

        parent::removeEffects($inventory, $item);
    }

    public function addEffects(ArmorInventory $inventory, PmItem $item): void
    {
        $itemBlock = $item->getBlock();

        if ($itemBlock instanceof MobHead && $itemBlock->getMobHeadType() === MobHeadType::DRAGON) {
            $inventory->getHolder()->setNameTagAlwaysVisible(false);
        }

        parent::addEffects($inventory, $item);
    }
}