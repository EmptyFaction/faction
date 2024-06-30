<?php

namespace Faction\handler;

use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use function mb_strtolower;

class ScoreFactory
{
    private const OBJECTIVE_NAME = "objective";
    private const CRITERIA_NAME = "dummy";

    private static array $scoreboards = [];

    public static function updateScoreboard(Player $player): void
    {
        $session = Session::get($player);

        if (!$session->data["scoreboard"]) {
            return;
        }

        if (self::hasScore($player)) {
            self::setScore($player, "§8» §cEmpty §8«");

            $rank = ($player->getName() === $player->getDisplayName()) ? ucfirst(strtolower($session->data["rank"])) : "Joueur";
            $faction = Faction::hasFaction($player) ? Faction::getFactionUpperName($session->data["faction"]) : "Aucune";

            $money = Util::formatNumberWithSuffix($session->data["money"]);
            $ecoins = Util::formatNumberWithSuffix($session->data["ecoin"]);

            $lines = [
                "§f ",
                "§l§c" . $player->getDisplayName(),
                "  §8| §fGrade: §c" . $rank,
                "  §8| §fFaction: §c" . $faction,
                "  §8| §fArgent: §c" . $money,
                "  §8| §fECoins: §c" . $ecoins,
                "§r ",
                "§c§lINFOS ",
                "  §8| §fJoueurs: §c" . count(Main::getInstance()->getServer()->getOnlinePlayers()),
                "  §8| §fVote: §c0/0",
                "§7 ",
                "   §7emptyfac.com   "
            ];

            foreach ($lines as $key => $value) {
                self::setScoreLine($player, $key + 1, $value);
            }
        } else {
            self::setScore($player, "§8» §cNitro §8«");
            self::updateScoreboard($player);
        }
    }

    public static function hasScore(Player $player): bool
    {
        return isset(self::$scoreboards[mb_strtolower($player->getXuid())]);
    }

    public static function setScore(Player $player, string $displayName, int $slotOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING, string $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR, string $objectiveName = self::OBJECTIVE_NAME, string $criteriaName = self::CRITERIA_NAME): void
    {
        if (isset(self::$scoreboards[mb_strtolower($player->getXuid())])) {
            self::removeScore($player);
        }

        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = $displaySlot;

        $pk->objectiveName = $objectiveName;
        $pk->displayName = $displayName;

        $pk->criteriaName = $criteriaName;
        $pk->sortOrder = $slotOrder;

        $player->getNetworkSession()->sendDataPacket($pk);
        self::$scoreboards[mb_strtolower($player->getXuid())] = $objectiveName;
    }

    public static function removeScore(Player $player): void
    {
        $objectiveName = self::$scoreboards[mb_strtolower($player->getXuid())] ?? self::OBJECTIVE_NAME;
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);
        unset(self::$scoreboards[mb_strtolower($player->getXuid())]);
    }

    public static function setScoreLine(Player $player, int $line, string $message, int $type = ScorePacketEntry::TYPE_FAKE_PLAYER): void
    {
        $entry = new ScorePacketEntry();

        $entry->objectiveName = self::$scoreboards[mb_strtolower($player->getXuid())] ?? self::OBJECTIVE_NAME;
        $entry->type = $type;
        $entry->customName = $message;
        $entry->score = $line;
        $entry->scoreboardId = $line;

        $pk = new SetScorePacket();

        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;

        $player->getNetworkSession()->sendDataPacket($pk);
    }
}