<?php

namespace Faction\command\faction\subcommands;

use CortexPE\Commando\args\TargetPlayerArgument;
use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Invite extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "invite",
            "Inviter un joueur dans sa faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["invit", "add"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        /** @noinspection PhpDeprecationInspection */
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (count(Faction::getFactionMembers($faction, false)) >= 15) {
            $sender->sendMessage(Util::PREFIX . "Votre faction ne peut pas comporter plus de 15 joueurs");
            return;
        } else if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $targetSession = Session::get($target);

        if (Faction::hasFaction($target)) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué est déjà dans une faction");
            return;
        } else if (!in_array($faction, $targetSession->data["invite"])) {
            $targetSession->data["invite"][] = $faction;
        }

        $target->sendMessage(Util::PREFIX . "Vous avez été invité à rejoindre la faction §c" . Faction::getFactionUpperName($faction) . "\n§f/f accept §c" . $faction . " §fpour accepter l'invitation");

        Cache::$factions[$faction]["logs"][time()] = "§c" . $sender->getName() . " §finvite §c" . $target->getName();
        Faction::broadcastFactionMessage($faction, "Le joueur §c" . $target->getName() . " §fvient d'être invité dans votre faction");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
    }
}