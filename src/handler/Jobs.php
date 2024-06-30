<?php

namespace Faction\handler;

use Faction\Session;
use Faction\Util;
use pocketmine\player\Player;
use pocketmine\world\sound\BlazeShootSound;

class Jobs
{
    public static function getProgressBar(Player $player, string $job, string $option = null): string
    {
        $level = self::getLevel($player, $job);
        $xp = self::getXp($player, $job);

        $nextXp = Cache::$config["jobs"]["lvls"][$level];

        if ($option === "UI") {
            if ($level === 20) {
                return "0§c/§8-1 §c- §8Level: §c" . $level;
            } else {
                return $xp . "§c/§8" . $nextXp . " §c- §8Level: §c" . $level;
            }
        }

        if ($level === 20) {
            return "§cNiveau maximum atteint";
        } else {
            $progress = intval(max(1, round((($xp / $nextXp) * 100) / 2, 2)));
            return "§a" . str_repeat("|", $progress) . "§c" . str_repeat("|", 50 - $progress);
        }
    }

    public static function getLevel(Player $player, string $job): int|float
    {
        return Session::get($player)->data["jobs"][$job]["lvl"] ?? 1;
    }

    public static function getXp(Player $player, string $job): int|float
    {
        return Session::get($player)->data["jobs"][$job]["xp"] ?? 0;
    }

    public static function addXp(Player $player, string $job, int|float $xp, bool $tip = true): void
    {
        if ($player->isCreative()) {
            return;
        }

        $session = Session::get($player);

        $rank = Rank::getEqualRankBySession($session);
        $tax = Rank::getRankValue($rank, "tax");

        $level = self::getLevel($player, $job);
        $xp = ($level === 20) ? 0 : round($xp * (1 + (25 - $tax) / 100));

        $nextTotal = Cache::$config["jobs"]["lvls"][$level];
        $total = self::getXp($player, $job) + $xp;

        if ($tip) {
            $player->sendTip(Util::PREFIX . "+ §c" . $xp . " §f" . $job);
        }

        if ($total > $nextTotal) {
            $newXp = $total - $nextTotal;
            $nextLevel = $level + 1;

            $session->data["jobs"][$job]["lvl"] = $nextLevel;
            $session->data["jobs"][$job]["xp"] = $newXp;

            $session->addValue("money", $nextLevel * 2000);

            $player->sendMessage(Util::PREFIX . "Vous venez de passer niveau §c" . $nextLevel . " §fdu métier de §c" . $job . " §f!!");
            $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §c" . $nextLevel * 2000 . " §fpièces pour vos récompenses de métiers !");

            $player->broadcastSound(new BlazeShootSound());

            if (isset(Cache::$config["jobs"]["rewards"][strval($nextLevel)])) {
                $data = Cache::$config["jobs"]["rewards"][strval($nextLevel)];
                $data = explode(":", $data);

                switch (intval($data[0])) {
                    case 0:
                        $name = $data[1];
                        $count = intval($data[2]);

                        $item = Util::getItemByName($name)->setCount($count);
                        Util::addItem($player, $item);

                        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §c" . $data[3] . " §fpour vos récompenses de métier !");
                        break;
                }
            }
        } else {
            $actualXp = self::getXp($player, $job);
            $session->data["jobs"][$job]["xp"] = $actualXp + $xp;
        }
    }
}