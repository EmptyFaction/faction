<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\command\player\Kit;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class GiveKit extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "givekit",
            "Donne un kit à un joueur"
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
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);
        $items = Kit::getKits()[$args["kit"]]["items"];

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        Util::addItems($target, $items);

        $sender->sendMessage(Util::PREFIX . "Vous venez de donner un kit §c" . $args["kit"] . " §fau joueur §c" . $target->getName());
        $target->sendMessage(Util::PREFIX . "Vous venez de recevoir le kit §c" . $args["kit"] . " §fde la part de §c" . $sender->getName());
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(1, new OptionArgument("kit", array_keys(Kit::getKits())));
    }
}