<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Nightvision extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "nightvision",
            "Active la night vision"
        );

        $this->setAliases(["nv"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->data["night_vision"]) {
                $sender->getEffects()->remove(VanillaEffects::NIGHT_VISION());
                $session->data["night_vision"] = false;

                $sender->sendMessage(Util::PREFIX . "Vous venez de désactiver la nightvision");
            } else {
                $session->data["night_vision"] = true;
                Util::givePlayerPreferences($sender);

                $sender->sendMessage(Util::PREFIX . "Vous venez d'activer la nightvision");
            }
        }
    }

    protected function prepare(): void
    {
    }
}