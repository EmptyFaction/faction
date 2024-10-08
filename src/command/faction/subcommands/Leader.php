<?php

namespace Faction\command\faction\subcommands;

use CortexPE\Commando\args\RawStringArgument;
use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Leader extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "leader",
            "Définir un nouveau chef de faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["lead"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if (Faction::getFactionRank($sender) !== "leader") {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez définir un nouveau chef seulement si vous en êtes le chef");
            return;
        } else if (!in_array($args["membre"], Faction::getFactionMembers($faction, false))) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur n'est pas dans votre faction (verifiez les majuscules)");
            return;
        } else if ($args["membre"] === $sender->getName()) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas passer de chef à chef");
            return;
        }

        $rank = Faction::getFactionRank($faction, $args["membre"]);
        unset(Cache::$factions[$faction]["members"][$rank . "s"][array_search($args["membre"], Cache::$factions[$faction]["members"][$rank . "s"])]);

        Cache::$factions[$faction]["members"]["officiers"][] = $sender->getName();
        Cache::$factions[$faction]["members"]["leader"] = $args["membre"];

        Cache::$factions[$faction]["logs"][time()] = "§c" . $sender->getName() . " §fdonne son lead a §c" . $args["membre"];
        Faction::broadcastFactionMessage($faction, "Le joueur §c" . $args["membre"] . " §fest votre nouveau chef de faction");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("membre"));
    }
}