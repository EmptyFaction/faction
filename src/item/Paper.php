<?php

namespace Faction\item;

use Faction\Session;
use Faction\Util;
use pocketmine\event\player\PlayerItemUseEvent;

class Paper extends Item
{
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (!is_null($item->getNamedTag()->getTag("money"))) {
            $money = $item->getNamedTag()->getInt("money");

            Session::get($player)->addValue("money", $money);
            $player->sendMessage(Util::PREFIX . "§fVous venez de recevoir §c" . $money . "$ !");

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        } else if (!is_null($item->getNamedTag()->getTag("xp_boost"))) {
            $boost = $item->getNamedTag()->getInt("xp_boost");
            $duration = $item->getNamedTag()->getInt("duration");

            $session = Session::get($player);

            if ($session->inCooldown("xp_boost")) {
                $current = $session->getCooldownData("xp_boost")[1];
                $remaining = $session->getCooldownData("xp_boost")[0];

                $player->sendMessage(Util::PREFIX . "Vous possèdez déjà un boost de §c" . $current . "%§f, pour utiliser un nouveau boost veuillez attendre: §c" . Util::formatDurationFromSeconds($remaining));
                return true;
            }

            $session->setCooldown("xp_boost", $duration, [$boost]);
            $player->sendMessage(Util::PREFIX . "§fVous venez de recevoir un boost de §c" . $boost . "% §fsur l'xp de vos jobs pendant §c" . Util::formatDurationFromSeconds($duration));

            $this->projectileSucces($player, $item);
            $event->cancel();

            return true;
        }

        return false;
    }
}