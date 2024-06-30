<?php

namespace Faction\command\faction\subcommands\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Leader extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "leader", "Modifie le chef d'une faction");
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $faction = strtolower($args["faction"]);
        $member = $args["membre"];

        return;

        if (!Faction::exists($faction)) {
            $sender->sendMessage(Util::PREFIX . "La faction §c" . $faction . " §fn'existe pas");
            return;
        } else if (!in_array($member, Faction::getFactionMembers($faction, false))) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans la faction que vous avez indiqué (verifiez les majs)");
            return;
        } else if ($member === $sender->getName()) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur est déjà le chef de sa faction");
            return;
        }

        $rank = Faction::getFactionRank($faction, $member);

        unset(Cache::$factions[$faction]["members"][$rank . "s"][array_search($member, Cache::$factions[$faction]["members"][$rank . "s"])]);
        Cache::$factions[$faction]["members"]["leader"] = $member;

        Faction::broadcastFactionMessage($faction, "Le joueur §c" . $member . " §fest votre nouveau chef de faction");
        $sender->sendMessage(Util::PREFIX . "Vous venez de mettre la tête de la faction §c" . $faction . " §fà §c" . $member);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
        $this->registerArgument(1, new RawStringArgument("membre"));
    }
}