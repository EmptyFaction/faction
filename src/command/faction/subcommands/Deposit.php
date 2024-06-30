<?php

namespace Faction\command\faction\subcommands;

use CortexPE\Commando\args\IntegerArgument;
use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Deposit extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "deposit",
            "Déposer de l'argent dans la banque de faction"
        );

        $this->setAliases(["d"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $amount = intval($args["montant"]);

        if (0 >= $amount) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas déposer un montant négatif");
            return;
        } else if ($amount > $session->data["money"]) {
            $sender->sendMessage(Util::PREFIX . "Votre monnaie est infèrieur à §c" . $amount);
            return;
        }

        Cache::$factions[$faction]["money"] += $amount;
        Cache::$factions[$faction]["logs"][time()] = "§c" . $sender->getName() . " §fa déposé §c" . $amount . "§f$ dans la banque de faction";

        $session->addValue("money", $amount, true);
        Faction::broadcastFactionMessage($faction, "§c[§fF§c] §c" . $sender->getName() . " §fvient de déposer §c" . $amount . " §fpièces dans la banque de faction");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("montant"));
    }
}