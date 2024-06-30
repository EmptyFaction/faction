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

class AllyAccept extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "allyaccept",
            "Accepte la dernière demande d'alliance recu"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if (!is_null(Faction::getAlly($faction))) {
            $sender->sendMessage(Util::PREFIX . "Vous possèdez déjà une alliance");
            return;
        }

        $ally = Cache::$pendingAlly[$faction] ?? null;

        if (is_null($ally)) {
            $sender->sendMessage(Util::PREFIX . "Vous n'avez aucune demande d'alliance en attente");
            return;
        } else if (!is_null(Faction::getAlly($ally))) {
            $sender->sendMessage(Util::PREFIX . "La faction §c" . $ally . "§f possède déjà une alliance");
            return;
        }

        Faction::setAlly($faction, $ally);
        Faction::setAlly($ally, $faction);

        unset(Cache::$pendingAlly[$faction]);

        Faction::broadcastFactionMessage($faction, "Votre faction vient d'accepter la demande d'alliance de la part de la faction §c" . $ally);
        Faction::broadcastFactionMessage($ally, "La faction §c" . $faction . " §fa accepté votre demande d'alliance");
    }

    protected function prepare(): void
    {
    }
}