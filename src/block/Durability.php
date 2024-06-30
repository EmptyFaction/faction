<?php

namespace Faction\block;

use Faction\handler\Cache;
use Faction\handler\trait\CooldownTrait;
use Faction\Util;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;

abstract class Durability extends Block
{
    use CooldownTrait;

    abstract public function getDurability(): int;

    public function onBreak(BlockBreakEvent $event): bool
    {
        $position = $event->getBlock()->getPosition();
        $format = $position->__toString();

        if (isset(Cache::$durability[$format])) {
            unset(Cache::$durability[$format]);
        }

        return parent::onBreak($event);
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        $position = $event->getBlock()->getPosition();
        $format = $position->__toString();

        if (isset(Cache::$durability[$format])) {
            if (!$this->inCooldown($player)) {
                $player->sendMessage(Util::PREFIX . "Ce bloc possède encore §c" . (Cache::$durability[$format] + 1) . " §fdurabilité avant de §cexploser");
                $this->setCooldown($player, 1);
            }
        }
        return false;
    }
}