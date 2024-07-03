<?php /** @noinspection PhpUnused */

namespace Faction\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use Faction\task\teleportation\TeleportationTask;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Back extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "back",
            "Se téléporter à l'emplacement de votre dernière mort"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!Rank::hasRank($sender, "vip")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas le grade necessaire pour utiliser cette commande");
                return;
            }

            $session = Session::get($sender);

            if ($session->inCooldown("back")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("back")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §c/back §fque dans: §c" . $format);
                return;
            } else if ($session->data["back"] === null) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas utiliser cette commande car vous n'avez aucune mort récente");
                return;
            }

            list($x, $y, $z) = explode(":", $session->data["back"]);

            $position = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());
            $session->setCooldown("back", 60 * 5);

            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, $position, "back"), 20);
        }
    }

    protected function prepare(): void
    {
    }
}