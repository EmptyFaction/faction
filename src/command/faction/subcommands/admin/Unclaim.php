<?php

namespace Faction\command\faction\subcommands\admin;

use CortexPE\Commando\BaseSubCommand;
use Faction\Main;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;

class Unclaim extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "unclaim",
            "Unclaim le claim ou vous êtes"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        // TODO FAIRE UN F ADMIN UNCLAIM
    }

    protected function prepare(): void
    {
    }
}