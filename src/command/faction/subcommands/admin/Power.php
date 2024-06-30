<?php

namespace Faction\command\faction\subcommands\admin;

use CortexPE\Commando\BaseSubCommand;
use Faction\command\faction\subcommands\admin\power\Add;
use Faction\command\faction\subcommands\admin\power\Remove;
use Faction\Main;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Power extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(Main::getInstance(), "power", "Retire du power à une faction");
        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Add());
        $this->registerSubCommand(new Remove());
    }
}