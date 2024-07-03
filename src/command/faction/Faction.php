<?php /** @noinspection PhpUnused */

namespace Faction\command\faction;

use CortexPE\Commando\BaseCommand;
use Faction\command\faction\subcommands\Accept;
use Faction\command\faction\subcommands\Admin;
use Faction\command\faction\subcommands\Ally;
use Faction\command\faction\subcommands\AllyAccept;
use Faction\command\faction\subcommands\AllyBreak;
use Faction\command\faction\subcommands\Border;
use Faction\command\faction\subcommands\Chat;
use Faction\command\faction\subcommands\Claim;
use Faction\command\faction\subcommands\Create;
use Faction\command\faction\subcommands\Delete;
use Faction\command\faction\subcommands\Delhome;
use Faction\command\faction\subcommands\Deposit;
use Faction\command\faction\subcommands\Home;
use Faction\command\faction\subcommands\Info;
use Faction\command\faction\subcommands\Invite;
use Faction\command\faction\subcommands\Kick;
use Faction\command\faction\subcommands\Leader;
use Faction\command\faction\subcommands\Leave;
use Faction\command\faction\subcommands\Logs;
use Faction\command\faction\subcommands\Map;
use Faction\command\faction\subcommands\Permissions;
use Faction\command\faction\subcommands\Promote;
use Faction\command\faction\subcommands\Rename;
use Faction\command\faction\subcommands\Sethome;
use Faction\command\faction\subcommands\Tl;
use Faction\command\faction\subcommands\Top;
use Faction\command\faction\subcommands\Unclaim;
use Faction\command\faction\subcommands\Withdraw;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Faction extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct($plugin, "faction", "Les commandes relatant au faction", ["f"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new Accept());
        $this->registerSubCommand(new Admin());
        $this->registerSubCommand(new Ally());
        $this->registerSubCommand(new AllyAccept());
        $this->registerSubCommand(new AllyBreak());
        $this->registerSubCommand(new Border());
        $this->registerSubCommand(new Chat());
        $this->registerSubCommand(new Claim());
        $this->registerSubCommand(new Create());
        $this->registerSubCommand(new Delete());
        $this->registerSubCommand(new Delhome());
        $this->registerSubCommand(new Deposit());
        $this->registerSubCommand(new Home());
        $this->registerSubCommand(new Info());
        $this->registerSubCommand(new Invite());
        $this->registerSubCommand(new Kick());
        $this->registerSubCommand(new Leader());
        $this->registerSubCommand(new Leave());
        $this->registerSubCommand(new Logs());
        $this->registerSubCommand(new Map());
        $this->registerSubCommand(new Permissions());
        $this->registerSubCommand(new Promote());
        $this->registerSubCommand(new Rename());
        $this->registerSubCommand(new Sethome());
        $this->registerSubCommand(new Tl());
        $this->registerSubCommand(new Top());
        $this->registerSubCommand(new Unclaim());
        $this->registerSubCommand(new Withdraw());
    }
}