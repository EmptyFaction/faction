<?php /** @noinspection PhpUnused */

namespace Faction\command\util;

use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use Faction\task\teleportation\TeleportationTask;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class Spawn extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "spawn",
            "Se téléporte au spawn"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            }

            $pos = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();

            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask(
                $sender,
                Position::fromObject($pos->add(0.5, 0, 0.5), $pos->getWorld()),
                "spawn"
            ), 20);
        }
    }

    protected function prepare(): void
    {
    }
}