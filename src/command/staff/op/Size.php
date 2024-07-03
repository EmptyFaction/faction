<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\FloatArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Size extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "size",
            "Permet de changer sa taille ou celle d'un autre joueur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $size = $args["taille"];
        $player = $args["joueur"] ?? $sender->getName();

        $player = Main::getInstance()->getServer()->getPlayerExact($player);

        if (!$player instanceof Player) {
            if ($sender instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur que vous avez indiqué n'est pas connecté");
            }
            return;
        } else if (0.05 >= $size || $size > 99) {
            $sender->sendMessage(Util::PREFIX . "La taille indiqué n'est pas correcte elle doit être entre §c0.05 §fet §c99");
            return;
        }

        $player->setScale($size);

        if ($player === $sender) {
            $sender->sendMessage(Util::PREFIX . "Vous venez de vous mettre à la taille §c" . $size);
        } else {
            $sender->sendMessage(Util::PREFIX . "Vous venez de mettre le joueur §c" . $player->getName() . " §fà la taille §c" . $size);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new FloatArgument("taille"));
        $this->registerArgument(1, new TargetPlayerArgument(true, "joueur"));
    }
}