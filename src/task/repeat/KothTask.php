<?php

namespace Faction\task\repeat;

use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\player\Player;
use skymin\bossbar\BossBarAPI;

class KothTask
{
    public static ?int $currentKoth = null;
    public static ?string $currentPlayer = null;

    public static function run(): void
    {
        $players = Main::getInstance()->getServer()->getOnlinePlayers();

        if (!is_numeric(self::$currentKoth)) {
            return;
        }

        foreach ($players as $player) {
            $playerName = self::$currentPlayer;
            $playerName = is_null($playerName) ? "Aucun Joueur" : $playerName;

            $percentage = ((KothTask::$currentKoth + 1) / 180);

            BossBarAPI::getInstance()->sendBossBar(
                $player,
                "Koth | Controlé par " . $playerName,
                1,
                $percentage,
                BossBarAPI::COLOR_RED
            );
        }

        if (is_null(self::$currentPlayer)) {
            foreach ($players as $player) {
                if (!Session::get($player)->data["staff_mod"][0] && $player->isAlive() && Util::insideZone($player->getPosition(), "koth")) {
                    self::$currentPlayer = $player->getName();
                    return;
                }
            }

            self::$currentKoth = 180;
        } else {
            $player = Main::getInstance()->getServer()->getPlayerExact(self::$currentPlayer);

            if (!$player instanceof Player || Session::get($player)->data["staff_mod"][0] || !$player->isAlive() || !Util::insideZone($player->getPosition(), "koth")) {
                self::$currentPlayer = null;
                self::$currentKoth = 180;
                return;
            }
        }

        self::$currentKoth--;

        if (0 >= self::$currentKoth) {
            $name = is_null(self::$currentPlayer) ? "aucun joueur" : self::$currentPlayer;
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "L'event §cKOTH §fvient de se terminer et a été gagné par §c" . $name);

            foreach ($players as $player) {
                BossBarAPI::getInstance()->hideBossBar($player, 1);
            }

            if (!is_null(self::$currentPlayer)) {
                $player = Main::getInstance()->getServer()->getPlayerExact(self::$currentPlayer);

                if ($player instanceof Player) {
                    $session = Session::get($player);

                    $session->addValue("money", 25000);

                    if (Faction::hasFaction($player)) {
                        Faction::addPower($session->data["faction"], mt_rand(15, 25));
                    }

                    $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §c25k$ §fcar vous avez gagné l'event koth");
                }
            }

            self::$currentKoth = null;
            self::$currentPlayer = null;
        }
    }
}