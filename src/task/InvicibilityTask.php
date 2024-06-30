<?php

namespace Faction\task;

use Faction\Util;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class InvicibilityTask extends Task
{
    public function __construct(private readonly Player $player, private int $seconds)
    {
    }

    public function onRun(): void
    {
        if (!$this->player->isOnline()) {
            $this->getHandler()->cancel();
            return;
        } else if ($this->seconds <= 0) {
            $this->player->sendTip(Util::PREFIX . "Vous n'êtes plus invincible");
            $this->getHandler()->cancel();
            return;
        }

        $this->player->sendTip(Util::PREFIX . "Vous êtes invincible pendant §c" . $this->seconds . " §fseconde(s)");
        $this->seconds--;
    }
}