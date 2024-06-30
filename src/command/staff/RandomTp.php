<?php /** @noinspection PhpUnused */

namespace Faction\command\staff;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class RandomTp extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "randomtp",
            "Se téléporte à un joueur au hasard connecté"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $players = Main::getInstance()->getServer()->getOnlinePlayers();
            $target = $players[array_rand($players)];

            if (in_array($sender->getName(), Vanish::$vanish)) {
                foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
                    if ($player->hasPermission("staff.group") || $player->getName() === $sender->getName()) {
                        continue;
                    }
                    $player->hidePlayer($sender);
                }
            } else if (count($players) === 1) {
                $sender->sendMessage(Util::PREFIX . "Vous êtes seul sur le serveur");
                return;
            }

            $sender->teleport($target->getPosition());
            $sender->sendMessage(Util::PREFIX . "Vous avez été téléporté sur le joueur §c" . $target->getName());
        }
    }

    protected function prepare(): void
    {
    }
}