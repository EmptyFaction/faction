<?php

namespace Faction\command\faction\subcommands\admin;

use CortexPE\Commando\BaseSubCommand;
use Faction\command\faction\subcommands\admin\claim\Here;
use Faction\Main;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Claim extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "claim",
            "Permet de gérer les claims"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Here());
    }
}