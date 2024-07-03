<?php /** @noinspection PhpUnused */

namespace Faction\command\player\home;

use CortexPE\Commando\args\RawStringArgument;
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

class Home extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "home",
            "Permet de se téléporter à un home"
        );

        $this->setAliases(["h"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $home = $args["nom"] ?? null;

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            }

            $list = self::getHomes($session);

            if ($home === null) {
                $sender->sendMessage(Util::PREFIX . "Voici la liste de vos homes: " . $list);
                return;
            } else if (!isset($session->data["homes"][$home])) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez aucun home au nom de §c" . $home . "§f, voici la liste de vos homes: " . $list);
                return;
            }

            list($x, $y, $z) = explode(":", $session->data["homes"][$home]);
            $pos = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());

            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, $pos, "home"), 20);
        }
    }

    public static function getHomes(Session $session): string
    {
        $homes = $session->data["homes"];
        $count = count($homes);

        if (1 > $count) {
            return "§cAucun home";
        } else {
            $list = implode("§f, §c", array_keys($homes));
            return "§c" . $list . " §f(§c" . $count . "§f)";
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom", true));
    }
}