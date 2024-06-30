<?php

namespace Faction\command\faction\subcommands\admin;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use Faction\command\faction\subcommands\Logs as FactionLogs;
use Faction\handler\Faction;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Logs extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "logs", "Regarder les logs d'une faction");
        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $faction = strtolower($args["faction"]);

            if (!Faction::exists($faction)) {
                $sender->sendMessage(Util::PREFIX . "La faction §c" . $faction . " §fn'existe pas");
                return;
            }

            FactionLogs::showLogsForm($sender, $faction);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("faction"));
    }
}