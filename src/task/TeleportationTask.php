<?php

namespace Faction\task;

use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\World;

class TeleportationTask extends Task
{
    public function __construct(
        private readonly Player   $player,
        private readonly Position $position,
        private readonly int      $invincibility = 0,
        private readonly bool     $rtp = false
    )
    {
        $session = Session::get($player);
        $time = Util::getTpTime($player);

        $player->sendMessage(Util::PREFIX . "Vous allez être téléporté dans §c" . max($time, 0) . " §fseconde(s), veuillez ne pas bouger");
        $player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 20 * ($time + 1), 1, false));

        $session->setCooldown("teleportation", $time, [Util::getPlace($player)]);
    }

    public function onRun(): void
    {
        $player = $this->player;
        $session = Session::get($player);

        $data = $session->getCooldownData("teleportation");

        if (!$player->isOnline()) {
            $this->getHandler()->cancel();
            return;
        } else if ($data[1] !== Util::getPlace($player)) {
            $player->sendMessage(Util::PREFIX . "Vous avez bougé lors de votre téléportation, elle a donc été annulée");
            $this->cancel($session);
            return;
        } else if ($session->inCooldown("combat")) {
            $player->sendMessage(Util::PREFIX . "Vous avez été mis en combat lors de votre téléportation, elle a donc été annulée");
            $this->cancel($session);
            return;
        } else if (!$session->inCooldown("teleportation")) {
            $seconds = $this->invincibility;

            if ($this->position->world instanceof World && $this->position->world->isLoaded()) {
                $player->teleport($this->position);
                $player->broadcastSound(new BlazeShootSound());

                if ($seconds > 0) {
                    $session->setCooldown("invincibility", $seconds);
                    Main::getInstance()->getScheduler()->scheduleRepeatingTask(new InvicibilityTask($player, $seconds), 20);
                }

                if ($this->rtp) {
                    $x = number_format($this->position->getX(), 2);
                    $y = $this->position->getFloorY();
                    $z = number_format($this->position->getZ(), 2);

                    $player->sendMessage(Util::PREFIX . "Vous avez téléporté en §fX: §c" . $x . " §fY: §c" . $y . " §fZ: §c" . $z);
                    $session->setCooldown("randomtp", 60 * 60 * 12);
                }
            } else {
                $player->sendMessage(Util::PREFIX . "Votre téléportation a été annulé dû à un problème technique, veuillez réessayer");
            }

            $this->getHandler()->cancel();
            return;
        }

        $player->sendTip(Util::PREFIX . "Téléportation dans: §c" . ($data[0] - time()));
        $player->broadcastSound(new ClickSound());
    }

    public function cancel(Session $session): void
    {
        $this->player->getEffects()->remove(VanillaEffects::BLINDNESS());
        $session->removeCooldown("teleportation");

        $this->getHandler()->cancel();
    }
}