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

class Withdraw extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "withdraw",
            "Retirer l'argent de la banque de faction"
        );

        $this->setAliases(["w", "retirer", "cashout"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        $amount = intval($args["montant"]);

        if (0 >= $amount) {
            $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas retirer un montant négatif");
            return;
        } else if ($amount > Cache::$factions[$faction]["money"]) {
            $sender->sendMessage(Util::PREFIX . "L'argent dans la banque de faction est infèrieur à §c" . $amount);
            return;
        }

        Cache::$factions[$faction]["money"] -= $amount;
        Cache::$factions[$faction]["logs"][time()] = "§c" . $sender->getName() . " §fa retiré §c" . $amount . "§f$ de la banque de faction";

        $session->addValue("money", $amount);
        Faction::broadcastFactionMessage($faction, "§c[§fF§c] §c" . $sender->getName() . " §fvient de retirer §c" . $amount . " §fpièces de la banque de faction");
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new IntegerArgument("montant"));
    }
}