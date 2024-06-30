<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Unclaim extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "unclaim",
            "Supprimer votre claim actuel"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $claim = Faction::inClaim($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorZ());

        if ($claim[1] !== $faction) {
            $sender->sendMessage(Util::PREFIX . "Vous devez être dans un de vos claims, ici vous n'êtes pas dans votre claim");
            return;
        }

        // Todo try
        Util::findAndRemoveValue(Cache::$factions[$faction]["claims"], $claim[2]);
        unset(Cache::$claims[$claim[2]]);

        Cache::$factions[$faction]["logs"][time()] = "§c" . $sender->getName() . " §funclaim l'ancien claim";
        Faction::broadcastFactionMessage($faction, "Votre faction vient de supprimer votre claim actuel");
    }

    protected function prepare(): void
    {
    }
}