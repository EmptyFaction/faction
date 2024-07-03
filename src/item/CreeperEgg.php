<?php

namespace Faction\item;

use Faction\entity\Creeper;
use pocketmine\entity\Location;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class CreeperEgg extends Item
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $blockReplace = $event->getBlock()->getSide($event->getFace());
        $entity = $this->createEntity($player->getWorld(), $blockReplace->getPosition()->add(0.5, 0, 0.5), lcg_value() * 360, 0);

        if ($item->hasCustomName()) {
            $entity->setNameTag($item->getCustomName());
        }

        $entity->spawnToAll();
        $this->projectileSucces($player, $item);

        return true;
    }

    public static function createEntity(World $world, Vector3 $pos, float $yaw, float $pitch): Creeper
    {
        return new Creeper(Location::fromObject($pos, $world, $yaw, $pitch));
    }
}