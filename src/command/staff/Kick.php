<?php /** @noinspection PhpUnused */

namespace Faction\command\staff;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\handler\Sanction;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Kick extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "kick",
            "Expulse un joueur du serveur"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            Sanction::sanctionForm($sender, $args["joueur"], "kick");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
    }
}