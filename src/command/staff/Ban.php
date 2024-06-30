<?php /** @noinspection PhpUnused */

namespace Faction\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\Rank;
use Faction\handler\Sanction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Ban extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "ban",
            "Permet de bannir les tricheurs ou autre"
        );


        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public static function checkBan(PlayerJoinEvent $event): bool
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $name = strtolower($player->getName());
        $value = null;

        foreach (Cache::$config["saves"] as $column) {
            foreach ($session->data[$column] as $datum) {
                if (isset(Cache::$bans[$datum])) {
                    $value = $datum;
                }
            }
        }

        if (isset(Cache::$bans[$name])) {
            $value = $name;
        }

        if (is_null($value)) {
            return false;
        }

        $data = Cache::$bans[$value];
        $time = $data[1];

        if ($time > time()) {
            $time = Util::formatDurationFromSeconds($time - time());

            $staff = $data[0];
            $reason = $data[2];

            Main::getInstance()->getServer()->getNetwork()->blockAddress($player->getNetworkSession()->getIp(), 600);
            $player->kick("§fVous êtes banni de nitrofaction.\n\n§fTemps restant: §c" . $time . "\n§fRaison: §c" . $reason . "\n§fStaff: §c" . $staff);

            return true;
        } else {
            return false;
        }
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $username = strtolower($args["joueur"]);

        if ($sender instanceof Player && Session::get($sender)->data["rank"] === "guide") {
            $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
        } else if (!isset(Cache::$players["upper_name"][$username])) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
        } else if ($sender instanceof Player) {
            Sanction::sanctionForm($sender, $username, "ban");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}