<?php

namespace Faction\block;

use Faction\handler\Box;
use Faction\handler\trait\CooldownTrait;
use pocketmine\event\player\PlayerInteractEvent;

class EndPortal extends Block
{
    use CooldownTrait;

    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if (Box::isBox($block) && !$player->isCreative()) {
            $event->cancel();

            if (!$this->inCooldown($player)) {
                $this->setCooldown($player, 1);

                if ($event::LEFT_CLICK_BLOCK === $event->getAction()) {
                    Box::openPreviewBox($player, $block);
                } else {
                    Box::openBox($player, $block);
                }
            }

            return true;
        }

        return false;
    }
}