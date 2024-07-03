<?php

namespace Faction\item;

use pocketmine\block\Crops;
use pocketmine\block\utils\BlockEventHelper;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\particle\HappyVillagerParticle;

class RapidFertilizer extends Item
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $item = $event->getItem();

        if ($block instanceof Crops && $block->getAge() < $block::MAX_AGE) {
            $b = clone $block;
            $b = $b->setAge($block::MAX_AGE);

            // TODO Improve l'animation

            if (BlockEventHelper::grow($block, $b, $player)) {
                $this->projectileSucces($player, $item);
                $block->getPosition()->getWorld()->addParticle($block->getPosition()->add(0.5, 1, 0.5), new HappyVillagerParticle());
            }

            return false;
        }

        return false;
    }
}