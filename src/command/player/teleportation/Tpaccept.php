<?php /** @noinspection PhpUnused */

namespace Faction\command\player\teleportation;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use Faction\task\teleportation\TeleportationTask;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Tpaccept extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "tpaccept",
            "Accepte une demande de téléportation"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            $teleportation = $session->data["teleportation"];

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($session->inCooldown("teleportation")) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en teleportation");
                return;
            } else if (count($teleportation) === 0) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez aucune demande de téléportation");
                return;
            }

            $player = $teleportation[0];
            $type = $teleportation[1];

            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix($player);

            if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur qui vous a envoyé la demande n'est plus connecté");
                return;
            }

            $session->data["teleportation"] = [];

            if ($type === "tpa") {
                Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($player, $sender->getPosition(), "tpa", 15), 20);
            } else {
                Main::getInstance()->getScheduler()->scheduleRepeatingTask(new TeleportationTask($sender, $player->getPosition(), "tpa", 15), 20);
            }

            $player->sendMessage(Util::PREFIX . "Le joueur §c" . $sender->getName() . " §fa accepté votre demande de téléportation");
            $player->sendMessage(Util::PREFIX . "Il sera téléporté dans un §c0 §fà §c5 §fsecondes par rapport à son grade et sa position !");
            $player->sendMessage(Util::PREFIX . "Il sera téléporté à la position actuel et non à la position dans 0 à 5 secondes");

            $sender->sendMessage(Util::PREFIX . "Vous avez accepté la demande de téléportation de §c" . $player->getName());
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(name: "joueur"));
    }
}