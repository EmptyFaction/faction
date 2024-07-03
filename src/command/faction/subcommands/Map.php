<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Map extends FactionCommand
{
    protected bool $requiresFaction = false;

    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "map",
            "Active ou désactive la vision des claims des factions dans vos alentours"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $session = Session::get($sender);

        if ($session->data["faction_map"]) {
            $session->data["faction_map"] = false;
            unset(Cache::$factionMapPlayers[$sender]);

            $sender->sendMessage(Util::PREFIX . "Vous ne verrez plus les claims dans vos alentours");
        } else {
            $session->data["faction_map"] = true;
            Util::givePlayerPreferences($sender);

            $sender->sendMessage(Util::PREFIX . "Vous voyez desormais la liste des claims dans vos alentours");
        }
    }

    protected function prepare(): void
    {
    }
}