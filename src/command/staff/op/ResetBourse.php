<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class ResetBourse extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "resetbourse",
            "Réinitialise la bourse"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        Util::resetBourse();
        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "La bourse vient d'être réinitialisé ! Profitez bien !");
    }

    protected function prepare(): void
    {
    }
}