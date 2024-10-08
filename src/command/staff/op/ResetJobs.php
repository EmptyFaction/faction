<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class ResetJobs extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "resetjobs",
            "Reset les métiers d'un joueur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @noinspection PhpDeprecationInspection */
        $player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (!$player instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        Session::get($player)->data["jobs"] = Cache::$config["default-data"]["jobs"];

        $sender->sendMessage(Util::PREFIX . "Vous venez de reset les métiers de §c" . $player->getName());
        $player->sendMessage(Util::PREFIX . "Vos métiers ont été resets par le staff §c" . $sender->getName());
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
    }
}