<?php

namespace Faction\command\player;


use CortexPE\Commando\BaseCommand;
use Faction\Session;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Keys extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "keys",
            "Ouvre le menu pour prévisualiser les kits"
        );

        $this->setAliases(["key"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $keys = Session::get($sender);
        }
    }

    protected function prepare(): void
    {
    }
}