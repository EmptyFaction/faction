<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Delete extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "delete",
            "Supprimer sa faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["del", "disband"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        Faction::broadcastFactionMessage($faction, "La faction dont vous êtiez n'existe désormais plus");

        foreach (Faction::getFactionMembers($faction, true) as $player) {
            $session->data["faction"] = null;
            $session->data["faction_chat"] = false;

            Rank::updateNameTag($player);
        }


        foreach (Cache::$factions[$faction]["claims"] as $claim) {
            unset(Cache::$claims[$claim]);
        }

        unset(Cache::$factions[$faction]);
    }

    protected function prepare(): void
    {
    }
}