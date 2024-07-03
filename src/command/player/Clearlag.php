<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\Util;
use Faction\task\PlayerTask;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Clearlag extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "clearlag",
            "Permet de savoir quand est-ce que le prochain clearlag"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $clearlag = PlayerTask::getNextClearlag();
        $sender->sendMessage(Util::PREFIX . "Le prochain clearlag automatique aura lieu dans §c" . Util::formatDurationFromSeconds($clearlag));
    }

    protected function prepare(): void
    {
    }
}