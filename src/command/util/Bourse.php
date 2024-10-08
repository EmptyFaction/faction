<?php /** @noinspection PhpUnused */

namespace Faction\command\util;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Bourse extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "bourse",
            "Affiche le prix des agricultures actuel"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $items = Util::getBourse();
        $bar = "§l§8-----------------------";

        $sender->sendMessage($bar);

        foreach ($items as $item) {
            list($name, , , $sell) = explode(":", $item);

            $sender->sendMessage("§c" . $name . "§f - Prix de vente: §c" . $sell . " §f\$§c/u - §fVendus: §c" . Util::formatNumberWithSuffix(Cache::$data["bourse"][$name]));
        }

        $sender->sendMessage($bar);
    }

    protected function prepare(): void
    {
    }
}