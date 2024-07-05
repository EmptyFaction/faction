<?php

namespace Faction\item;

use Faction\item\enchantment\ExtraVanillaEnchantments;
use Faction\Session;
use Faction\Util;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\math\VoxelRayTrace;
use pocketmine\player\Player;

class BoostedShovel extends Durable
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();

        $targets = $this->getLineOfBlocks($player, 2 + $this->getLevel($player));
        array_shift($targets);
        $targets = array_reverse($targets);

        if (Session::get($player)->inCooldown("recent_combat")) {
            $player->sendMessage(Util::PREFIX . "Pour utilisé une pelle boostée vous devez ne pas être en combat les dernières §c60 §fsecondes !");
            return true;
        }

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
        $enchant = ExtraVanillaEnchantments::getEnchantmentByName("boosted_shovel");

        // ? Has enchantment don't work
        foreach ($item->getEnchantments() as $enchantment) {
            if ($enchant->getName() === $enchantment->getType()->getName()) {
                return $enchantment->getLevel() + 1;
            }
        }

        return 1;
    }

    public function getMaxDurability(): int
    {
        return 2260;
    }
}