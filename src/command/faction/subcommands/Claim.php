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

class Claim extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "claim",
            "Claim une zone spécifique"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $claim = Faction::inClaim($sender->getPosition()->getFloorX(), $sender->getPosition()->getFloorZ());

        if (
            $claim[0] ||
            !Faction::canBuild($sender, $sender->getPosition(), "break") ||
            $sender->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()
        ) {
            $sender->sendMessage(Util::PREFIX . "Vous ne vous trouvez pas à une position libre d'être claim");
            return;
        } else if ($sender->isCreative()) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas claim en créatif");
            return;
        }

        $claims = Cache::$factions[$faction]["claims"];

        if (count($claims) >= count(Cache::$config["claims"])) {
            $sender->sendMessage(Util::PREFIX . "Votre faction a atteint le nombre maximum de claims (§c" . count($claims) . "§f)");
            return;
        }

        $powerNeeded = Cache::$config["claims"][count($claims)];

        if ($powerNeeded > Cache::$factions[$faction]["power"]) {
            $sender->sendMessage(Util::PREFIX . "Votre faction doit au minimum possèder §c" . $powerNeeded . " §fpowers pour avoir un nouveau claim");
            return;
        }

        Cache::$factions[$faction]["claims"][] = $claim[2];
        Cache::$claims[$claim[2]] = $faction;

        Cache::$factions[$faction]["logs"][time()] = "§c" . $sender->getName() . " §fa récupéré un nouveau claim";
        Faction::broadcastFactionMessage($faction, "Votre faction vient de récuperer un nouveau claim");
    }

    protected function prepare(): void
    {
    }
}