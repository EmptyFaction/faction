<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Sethome extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "sethome",
            "Défini un point de téléportation commun à une faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        if (Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld() !== $sender->getWorld()) {
            $sender->sendMessage(Util::PREFIX . "Vous devez être sur la map faction pour faire cela");
            return;
        }

        Cache::$factions[$faction]["home"] = round($sender->getPosition()->x) . ":" . round($sender->getPosition()->y) . ":" . round($sender->getPosition()->z);
        Cache::$factions[$faction]["logs"][time()] = "§c" . $sender->getName() . " §fsethome (§c" . Cache::$factions[$faction]["home"] . "§f)";

        $sender->sendMessage(Util::PREFIX . "Vous venez de définir le point de téléportation de votre home");
    }

    protected function prepare(): void
    {
    }
}