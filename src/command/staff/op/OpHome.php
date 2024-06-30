<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\command\player\home\Home;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class OpHome extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "ophome",
            "Permet de voir les homes d'un joueur ou de s'y téléporter"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);
            $home = $args["nom"] ?? null;

            if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                return;
            }

            $session = Session::get($player);
            $list = Home::getHomes($session);

            if ($home === null) {
                $sender->sendMessage(Util::PREFIX . "Voici la liste des homes de §c" . $player->getName() . "§f: " . $list);
                return;
            } else if (!isset($session->data["homes"][$home])) {
                $sender->sendMessage(Util::PREFIX . "§c" . $player->getName() . " §fpossède aucun home au nom de §c" . $home . "§f, voici la liste de ses homes: " . $list);
                return;
            }

            list($x, $y, $z) = explode(":", $session->data["homes"][$home]);
            $pos = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());

            $sender->teleport($pos);
            $sender->sendMessage(Util::PREFIX . "Vous avez été téléporté au home §c" . $home . " §fde §c" . $player->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(name: "joueur"));
        $this->registerArgument(1, new RawStringArgument("nom", true));
    }
}