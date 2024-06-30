<?php /** @noinspection PhpUnused */

namespace Faction\command\util;

use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Needfaction extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "needfaction",
            "Fait une annonce pour dire que vous recherchez une faction"
        );

        $this->setAliases(["nf"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("needfaction")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("needfaction")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §c/needfaction §fque dans: §c" . $format);
                return;
            }

            $session->setCooldown("needfaction", 60 * 60);
            Main::getInstance()->getServer()->broadcastMessage("§c[§f§lANNONCE§r§c] §c" . $sender->getName() . " §f§l» §r§frecherche actuellement une faction !");
        }
    }

    protected function prepare(): void
    {
    }
}