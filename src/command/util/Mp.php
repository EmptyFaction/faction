<?php /** @noinspection PhpUnused */

namespace Faction\command\util;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\sound\ClickSound;

class Mp extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "mp",
            "Envoie un message à un ou plusieurs joueurs"
        );


        $this->setAliases(["msg", "tell", "w", "dm", "m", "dm", "message"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("mute")) {
                $sender->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §c" . Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time()));
                return;
            }

            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix(array_shift($args));

            if ($player instanceof Player) {
                $playerSession = Session::get($player);

                if (in_array($player->getName(), $session->data["blocked"])) {
                    $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas envoyer de message à quelqu'un que vous avez bloqué");
                    return;
                } else if (in_array($sender->getName(), $playerSession->data["blocked"])) {
                    $sender->sendMessage(Util::PREFIX . "Le joueur §c" . $player->getName() . " §fvous a bloqué, vous ne pouvez pas lui envoyer de message");
                    return;
                }

                Main::getInstance()->getLogger()->info("[MP] [" . $sender->getName() . " » " . $player->getName() . "] " . implode(" ", $args));

                $session->data["reply"] = $player->getName();
                $playerSession->data["reply"] = $sender->getName();

                foreach ([$player, $sender] as $players) {
                    $players->sendMessage("§c[§fMP§c] §c[§f" . $sender->getName() . " " . Util::ARROW . $player->getName() . "§c] §f" . implode(" ", $args));
                    $players->broadcastSound(new ClickSound());
                }
            } else {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(1, new TextArgument("message"));
    }
}