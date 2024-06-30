<?php /** @noinspection PhpUnused */

namespace Faction\command\player\home;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Sethome extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "sethome",
            "Permet de définir un point de position d'un home"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $home = $args["nom"];

            $rank = Rank::getEqualRankBySession($session);
            $limit = Rank::getRankValue($rank, "homes");

            if (count($session->data["homes"]) >= $limit) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas avoir plus de §c" . $limit . " §fhomes acheter un grade suppérieur au votre pour améliorer cette limite");
                return;
            } else if (isset($session->data["homes"][$home])) {
                $sender->sendMessage(Util::PREFIX . "Vous possèdez déjà un home à ce nom");
                return;
            } else if ($sender->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas créer d'home dans ce monde");
                return;
            }

            $position = $sender->getPosition();
            $session->data["homes"][$home] = $position->getFloorX() . ":" . $position->getFloorY() . ":" . $position->getFloorZ();

            Main::getInstance()->getLogger()->info("Le joueur " . $sender->getName() . " a créé un home au nom de " . $home . " (" . $session->data["homes"][$home] . ")");
            $sender->sendMessage(Util::PREFIX . "Vous venez de définir un home au nom §c" . $home);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom"));
    }
}