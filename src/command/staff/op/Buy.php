<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Buy extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "buy",
            "Commande permettant de gérer les achats de la boutique"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $value = $args["valeur"];
        $player = $args["joueur"];

        if (isset(Cache::$config["ranks"][$value])) {
            Util::executeCommand("setrank \"" . $player . "\" " . $value);
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §c" . $player . " §fvient d'acheter le grade §c" . $value . " §fsur la boutique ! §chttps://nitrofaction.tebex.io");
        } else {
            if (str_contains($value, "unban")) {
                if (!isset(Cache::$bans[$player])) {
                    return;
                }

                $maxDays = explode("-", $value)[1];
                $maxDays = intval($maxDays);

                $data = Cache::$bans[$player];

                $seconds = $data[1] - time();
                $days = $seconds / 86400;

                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §c" . $player . " §fvient d'acheter un §cunban §fsur la boutique ! §chttps://nitrofaction.tebex.io");

                if ($maxDays > $days) {
                    Util::executeCommand("unban \"" . $player . "\"");
                }
            }
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
        $this->registerArgument(1, new RawStringArgument("valeur"));
    }
}