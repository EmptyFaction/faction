<?php

namespace Faction\item;

use Faction\entity\effect\Effects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class HangGlider extends Item
{
    public array $players = [];

    public function newHoldItem(Player $player): void
    {
        $this->newItem($player);
    }

    private function newItem(Player $player): void
    {
        $this->players[$player->getXuid()] = true;
        $player->getEffects()->add(new EffectInstance(Effects::$slowFalling, 9999 * 20, 1, false));
        $this->resetMotion($player);
    }

    private function resetMotion(Player $player): void
    {
        $player->setMotion(new Vector3(
            $player->getMotion()->getX() * 0.15,
            $player->getMotion()->getY() * 0.15,
            $player->getMotion()->getZ() * 0.15,
        ));
    }

    public function newHoldOffItem(Player $player): void
    {
        $this->newItem($player);
    }

    public function oldHoldItem(Player $player): void
    {
        $this->oldItem($player);
    }

    private function oldItem(Player $player): void
    {
        unset($this->players[$player->getXuid()]);
        $player->getEffects()->remove(Effects::$slowFalling);
    }

    public function oldHoldOffItem(Player $player): void
    {
        $this->oldItem($player);
    }

    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        $val = $this->players[$player->getXuid()] ?? true;

        if ($val) {
            $player->getEffects()->remove(Effects::$slowFalling);
            $this->players[$player->getXuid()] = false;
        } else {
            $player->getEffects()->add(new EffectInstance(Effects::$slowFalling, 9999 * 20, 1, false));
            $this->resetMotion($player);
            $this->players[$player->getXuid()] = true;
        }

        return true;
    }
}