<?php /** @noinspection PhpUnused */

namespace Faction\command\staff;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Session;
use Faction\Util;
use pocketmine\block\utils\DyeColor;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Staff extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "staff",
            "Active ou désactive le mode staff"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $data = $session->data["staff_mod"];

            if (!$data[0]) {
                if ($sender->getGamemode() === GameMode::SPECTATOR()) {
                    $sender->setGamemode(GameMode::SURVIVAL());
                }

                $session->data["staff_mod"] = [true, Util::savePlayerData($sender)];

                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer le staff mod");
                $this->sendItems($sender);
            } else {
                Util::restorePlayer($sender, $data[1]);

                $session->data["staff_mod"] = [false, []];
                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver le staff mod");

                if (in_array($sender->getName(), Vanish::$vanish)) {
                    $sender->sendMessage(Util::PREFIX . "Vous restez cependant toujours en vanish, n'oubliez pas de l'enlever");
                }

                if (!$sender->isCreative()) {
                    $sender->setAllowFlight(false);
                    $sender->setFlying(false);
                }
            }
        }
    }

    private function sendItems(Player $player): void
    {
        $player->setAllowFlight(true);
        $player->getArmorInventory()->clearAll();

        $player->getInventory()->clearAll();
        $player->getXpManager()->setXpLevel(0);

        $player->getInventory()->setItem(0, VanillaItems::BANNER()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::ARROW . "Spectateur" . Util::IARROW));
        $player->getInventory()->setItem(4, VanillaItems::SPIDER_EYE()->setCustomName("§r" . Util::ARROW . "Random Tp" . Util::IARROW));
        $player->getInventory()->setItem(5, VanillaItems::BLAZE_ROD()->setCustomName("§r" . Util::ARROW . "Freeze" . Util::IARROW));

        if (in_array($player->getName(), Vanish::$vanish)) {
            $player->getInventory()->setItem(1, VanillaItems::DYE()->setColor(DyeColor::GREEN())->setCustomName("§r" . Util::ARROW . "Vanish" . Util::IARROW));
        } else {
            $player->getInventory()->setItem(1, VanillaItems::DYE()->setColor(DyeColor::LIGHT_GRAY())->setCustomName("§r" . Util::ARROW . "Vanish" . Util::IARROW));
        }
    }

    protected function prepare(): void
    {
    }
}