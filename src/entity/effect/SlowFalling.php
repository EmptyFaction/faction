<?php

namespace Faction\entity\effect;

use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\player\Player;

class SlowFalling extends Effect
{
    public function canTick(EffectInstance $instance): bool
    {
        return true;
    }

    public function applyEffect(Living $entity, EffectInstance $instance, float $potency = 1.0, ?Entity $source = null): void
    {
        if (!($entity instanceof Player)) {
            $level = -$instance->getAmplifier();
            $directionVector = $entity->getDirectionVector()->multiply(4);

            $entity->addMotion($directionVector->x, (($level + 1) / 20 - $entity->getMotion()->y) / 5, $directionVector->z);
        }
    }
}
