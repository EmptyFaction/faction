<?php /** @noinspection PhpUnused */

namespace Faction\command\player\rank;

use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Annonce extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "annonce",
            "Fait passer une annonce au serveur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!Rank::hasRank($sender, "ultra")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas le grade necessaire pour utiliser cette commande");
                return;
            } else if ($session->inCooldown("mute")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas faire d'annonce en étant mute");
                return;
            } else if ($session->inCooldown("annonce")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("annonce")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §c/annonce §fque dans: §c" . $format);
                return;
            }

            $session->setCooldown("annonce", 60 * 120);
            Main::getInstance()->getServer()->broadcastMessage("§c[§f§lANNONCE§r§c] §f" . $sender->getName() . " §c§l» §r§f" . implode(" ", $args));
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TextArgument("message"));
    }
}