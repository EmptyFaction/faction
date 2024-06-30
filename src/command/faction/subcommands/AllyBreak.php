<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class AllyBreak extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "allybreak",
            "Supprime votre alliance actuel"
        );

        $this->setAliases(["unally", "breakalliancewith"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $ally = Faction::getAlly($faction);

        if (is_null($ally)) {
            $sender->sendMessage(Util::PREFIX . "Vous ne possèdez actuellement pas d'alliance");
            return;
        }

        Faction::setAlly($faction, null);
        Faction::setAlly($ally, null);

        Faction::broadcastFactionMessage($faction, "Votre faction vient de rompre son alliance avec la faction §c" . $ally);
        Faction::broadcastFactionMessage($ally, "La faction §c" . $faction . " §fvient de rompre son alliance avec votre faction");
    }

    protected function prepare(): void
    {
    }
}