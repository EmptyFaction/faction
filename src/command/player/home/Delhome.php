<?php /** @noinspection PhpUnused */

namespace Faction\command\player\home;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Delhome extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "delhome",
            "Permet de supprimer un home"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $home = $args["nom"];

            if (!isset($session->data["homes"][$home])) {
                $sender->sendMessage(Util::PREFIX . "Vous ne possèdez aucun home au nom de §c" . $home);
                return;
            }

            unset($session->data["homes"][$home]);
            $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer le home au nom §c" . $home);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("nom"));
    }
}