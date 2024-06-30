<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\task\TeleportationTask;
use Faction\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\world\Position;

class RandomTp extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "randomtp",
            "Téléporte au hasard sur la map"
        );

        $this->setAliases(["rtp"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("randomtp")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("randomtp")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §c/rtp §fque dans: §c" . $format);
                return;
            }

            Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, $this->generatePos(), 0, true), 20);
        }
    }

    public static function generatePos(): Position
    {
        $posX = mt_rand(1, 2) === 1 ? mt_rand(2000, 3000) : mt_rand(-3000, -2000);
        $posZ = mt_rand(1, 2) === 1 ? mt_rand(2000, 3000) : mt_rand(-3000, -2000);

        $world = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld();

        $highestBlockY = $world->getHighestBlockAt($posX, $posZ);
        $highestBlock = $world->getBlockAt($posX, $highestBlockY, $posZ);

        if ($highestBlock->hasSameTypeId(VanillaBlocks::WATER())) {
            return self::generatePos();
        }

        return new Position($posX + 0.5, $highestBlockY + 2, $posZ + 0.5, $world);
    }

    protected function prepare(): void
    {
    }
}