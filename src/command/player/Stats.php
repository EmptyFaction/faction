<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Stats extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "stats",
            "Récupere ses informations ou celle d'une autre personne"
        );

        $this->setAliases(["info"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $username = strtolower($args["joueur"] ?? $sender->getName());

            /** @noinspection PhpDeprecationInspection */
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($username);

            if (!isset(Cache::$players["upper_name"][$username])) {
                $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
                return;
            }

            if ($target instanceof Player) {
                $session = Session::get($target);
                Faction::hasFaction($target);

                $session->data["played_time"] += time() - $session->data["connection"];
                $session->data["connection"] = time();

                $data = $session->data;
            } else {
                $file = Util::getFile("data/players/" . $username);
                $data = $file->getAll();
            }

            $bar = "§l§8-----------------------";

            $faction = $data["faction"];
            $faction = (is_null($faction)) ? "Aucune Faction" : Faction::getFactionUpperName($faction);

            $sender->sendMessage($bar);
            $sender->sendMessage("§c[§f" . $faction . "§c] [§f" . ucfirst(strtolower($data["rank"])) . "§c] §f- §c" . $username);
            $sender->sendMessage("§cArgent: §f" . $data["money"]);
            $sender->sendMessage("§cECoins: §f" . $data["ecoin"]);
            $sender->sendMessage("§cKills: §f" . $data["kill"]);
            $sender->sendMessage("§cMorts: §f" . $data["death"]);
            $sender->sendMessage("§cRatio: §f" . round($data["kill"] / max(1, $data["death"]), 2));
            $sender->sendMessage("§cKillstreak: §f" . $data["killstreak"]);
            $sender->sendMessage("§cPremière connexion: §f" . Date("d/m/Y", intval($sender->getFirstPlayed()) / 1000));
            $sender->sendMessage("§cTemps de jeu: §f" . Util::formatDurationFromSeconds($data["played_time"]));
            $sender->sendMessage($bar);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(true, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur", true));
    }
}