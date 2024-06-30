<?php /** @noinspection PhpUnused */

namespace Faction\command\player\teleportation;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Tphere extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "tphere",
            "Envoie une demande de téléportation à un joueur pour qu'il soit téléporté sur vous"
        );

        $this->setAliases(["tpahere", "tph"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            } else if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
                return;
            } else if ($player->getName() === $sender->getName()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas vous téléporter sur vous même");
                return;
            } else if (in_array($player->getName(), $session->data["blocked"])) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas envoyer de demande de teleportation sur vous à quelqu'un que vous avez bloqué");
                return;
            } else if (in_array($sender->getName(), Session::get($player)->data["blocked"])) {
                $sender->sendMessage(Util::PREFIX . "Le joueur §c" . $player->getName() . " §fvous a bloqué, vous ne pouvez pas lui envoyer de demande de teleportation à vous");
                return;
            }

            Session::get($player)->data["teleportation"] = [$sender->getName(), "tphere"];

            $sender->sendMessage(Util::PREFIX . "Vous avez envoyé une demande de téléportation sur vous à §c" . $player->getName());
            $player->sendMessage(Util::PREFIX . "Vous avez reçu une demande de téléportation pour se téléporter à lui de la part de §c" . $sender->getName());
            $player->sendMessage(Util::PREFIX . "Pour accepter, utilisez la commande §c/tpaccept");
            $player->sendMessage(Util::PREFIX . "Pour refuser, utilisez la commande §c/tpdeny");
            $player->sendMessage(Util::PREFIX . "Si vous acceptez vous serez téléporté sur lui");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(name: "joueur"));
    }
}