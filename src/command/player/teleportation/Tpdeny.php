<?php /** @noinspection PhpUnused */

namespace Faction\command\player\teleportation;

use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Tpdeny extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "tpdeny",
            "Refuse une demande de téléportation"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $player = $session->data["teleportation"][0] ?? null;

            if ($player === null) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez aucune demande de téléportation");
                return;
            }

            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix($player);

            if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur qui vous a envoyé la demande n'est plus connecté");
                return;
            }

            $session->data["teleportation"] = [];

            $player->sendMessage(Util::PREFIX . "Le joueur §c" . $sender->getName() . " §fa refusé votre demande de téléportation");
            $sender->sendMessage(Util::PREFIX . "Vous avez refusé la demande de téléportation de §c" . $player->getName());
        }
    }

    protected function prepare(): void
    {
    }
}