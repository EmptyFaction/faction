<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\command\player\Kit;
use Faction\handler\Box;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class GiveKey extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "givekey",
            "Donne des clés à un joueur ou tout les joueurs"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($args["joueur"] === "@a") {
            Util::allSelectorExecute($sender, $this->getName(), $args);
            return;
        }

        /** @noinspection PhpDeprecationInspection */
        $player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (!$player instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        $box = $args["box"];
        $amount = intval($args["montant"] ?? 1);

        $item = Box::createKeyItem($box, $amount);
        Util::addItem($player, $item);

        $sender->sendMessage(Util::PREFIX . "Vous venez de donner §c" . $amount . " §fclé(s) de box §l§c" . strtoupper($box) . " §r§fau joueur §c" . $player->getName());
        $player->sendMessage(Util::PREFIX . "Vous venez de recevoir §c" . $amount . " §fclé(s) de box §l§c" . strtoupper($box) . " §r§fdu staff §c" . $sender->getName());
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(name: "joueur"));
        $this->registerArgument(1, new OptionArgument("box", array_map("strtolower", array_keys(Cache::$config["box"]))));
        $this->registerArgument(2, new IntegerArgument("montant", true));
    }
}