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

class Ally extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "ally",
            "Envoie une demande d'alliance à une faction"
        );

        $this->setAliases(["allywith"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $fac = $args["faction"];

        if (!Faction::exists($fac)) {
            $sender->sendMessage(Util::PREFIX . "La faction indiqué n'existe pas");
            return;
        } else if (!is_null(Faction::getAlly($faction))) {
            $sender->sendMessage(Util::PREFIX . "Votre faction possède déjà une alliance");
            return;
        } else if (!is_null(Faction::getAlly($fac))) {
            $sender->sendMessage(Util::PREFIX . "La faction indiqué possède déjà une alliance");
            return;
        }

        $sender->sendMessage(Util::PREFIX . "Vous venez d'envoyer une demande d'alliance à la faction §c" . $fac);
        Faction::broadcastFactionMessage($fac, "La faction §c" . $faction . "§f vous a envoyé une demande d'alliance pour accepter executez la commande: §c/f allyaccept");

        Cache::$pendingAlly[$fac] = $faction;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
    }
}