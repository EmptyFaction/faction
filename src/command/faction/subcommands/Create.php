<?php

namespace Faction\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Create extends FactionCommand
{
    protected bool $requiresFaction = false;

    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "create",
            "Créer sa faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $name = strtolower($args["nom"]);

        if (!is_null($faction)) {
            $sender->sendMessage(Util::PREFIX . "Vous appartenez déjà à une faction");
            return;
        } else if (Faction::exists($name)) {
            $sender->sendMessage(Util::PREFIX . "Ce nom de faction existe déjà");
            return;
        } else if (!ctype_alnum($name) || strlen($name) > 16) {
            $sender->sendMessage(Util::PREFIX . "Ce nom de faction est invalide ou trop long");
            return;
        }

        Cache::$factions[$name] = [
            "upper_name" => $args["nom"],
            "home" => "0:0:0",
            "claims" => [],
            "activity" => [],
            "power" => 0,
            "money" => 0,
            "ally" => null,
            "members" => [
                "leader" => $sender->getName(),
                "officiers" => [],
                "members" => [],
                "recruits" => []
            ],
            "permissions" => [
                "delete" => "leader",
                "leader" => "leader",
                "withdraw" => "leader",
                "permissions" => "leader",

                "invite" => "officier",
                "ally" => "officier",
                "allyaccept" => "officier",
                "allybreak" => "officier",
                "kick" => "officier",
                "sethome" => "officier",
                "delhome" => "officier",
                "claim" => "officier",
                "unclaim" => "officier",
                "logs" => "officier",
                "rename" => "officier",
                "demote" => "officier",
                "promote" => "officier",

                "place" => "member",
                "break" => "member",
                "chest" => "member",

                "fence-gates" => "recruit",
                "trapdoor" => "recruit",
                "door" => "recruit",
                "home" => "recruit",
            ]
        ];

        $session->data["faction"] = $name;

        $sender->sendMessage(Util::PREFIX . "Vous venez de créer votre faction §c" . $args["nom"] . " §f!");
        Rank::updateNameTag($sender);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom"));
    }
}