<?php /** @noinspection PhpUnused */

namespace Faction\command\staff;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Freeze extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "freeze",
            "Rend immobile un joueur"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @noinspection PhpDeprecationInspection */
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }

        if ($target->hasNoClientPredictions()) {
            $target->setNoClientPredictions(false);

            $target->sendMessage(Util::PREFIX . "Vous venez d'être unfreeze, vous pouvez désormais bouger");
            $sender->sendMessage(Util::PREFIX . "Vous venez de unfreeze le joueur §c" . $target->getName());
        } else {
            $target->setNoClientPredictions();

            $target->sendMessage(Util::PREFIX . "Vous venez d'être freeze, vous ne pouvez plus bouger");
            $sender->sendMessage(Util::PREFIX . "Vous venez de freeze le joueur §c" . $target->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
    }
}