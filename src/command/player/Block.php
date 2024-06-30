<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Block extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "block",
            "Bloquer un joueur"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix(array_shift($args));
            $session = Session::get($sender);

            if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                return;
            }

            $blocks = $session->data["blocked"];

            if (in_array($player->getName(), $blocks)) {
                $sender->sendMessage(Util::PREFIX . "Vous avez bien débloqué §c" . $player->getName());
                $player->sendMessage(Util::PREFIX . "Vous avez été débloqué par §c" . $sender->getName() . "§f, vous pouvez désormais lui envoyer des messages ou des demandes de téléportation");

                Util::findAndRemoveValue($blocks, $player->getName());
            } else {
                $sender->sendMessage(Util::PREFIX . "Vous avez bien bloqué §c" . $player->getName());
                $player->sendMessage(Util::PREFIX . "Vous avez été bloqué par §c" . $sender->getName() . "§f, vous ne pouvez désormais plus lui envoyer des messages ou des demandes de téléportation");

                $blocks[] = $player->getName();
            }

            // TODO Check si ça work pcq vsy
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(name: "joueur"));
    }
}