<?php

namespace Faction\command\faction\subcommands;

use CortexPE\Commando\BaseSubCommand;
use Faction\command\faction\subcommands\admin\Claim;
use Faction\command\faction\subcommands\admin\Delete;
use Faction\command\faction\subcommands\admin\Leader;
use Faction\command\faction\subcommands\admin\Logs;
use Faction\command\faction\subcommands\admin\Power;
use Faction\command\faction\subcommands\admin\Unclaim;
use Faction\Main;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Admin extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "admin", "Permet de gérer toutes les factions");
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Claim());
        $this->registerSubCommand(new Delete());
        $this->registerSubCommand(new Leader());
        $this->registerSubCommand(new Power());
        $this->registerSubCommand(new Logs());
        $this->registerSubCommand(new Unclaim());
    }
}