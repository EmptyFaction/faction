<?php

namespace Faction\command\faction\subcommands\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Delete extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "delete",
            "Supprime une faction"
        );

        $this->setAliases(["disband", "del"]);
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);

        if (!Faction::exists($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §c" . $faction . " §fn'existe pas");
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer la faction §c" . $faction);
        Faction::broadcastFactionMessage($faction, "La faction dont vous êtiez n'existe désormais plus");

        foreach (Faction::getFactionMembers($faction, true) as $player) {
            $session = Session::get($player);

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
        $this->registerArgument(0, new RawStringArgument("faction"));
    }
}