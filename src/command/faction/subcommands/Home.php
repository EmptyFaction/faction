<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use Faction\task\teleportation\TeleportationTask;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Home extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "home",
            "Se téléporter à l'home d'une faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
        $this->setAliases(["h"]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if ($session->inCooldown("combat")) {
            $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
            return;
        } else if ($session->inCooldown("teleportation")) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
            return;
        }

        $home = Cache::$factions[$faction]["home"];

        if (is_null($home)) {
            $sender->sendMessage(Util::PREFIX . "Votre faction n'a pas encore définit de home");
            return;
        }

        [$x, $y, $z] = explode(":", Cache::$factions[$faction]["home"]);

        $position = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, $position, "faction_home"), 20);
    }

    protected function prepare(): void
    {
    }
}