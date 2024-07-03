<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Box;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Session;
use Faction\task\async\VoteRequestTask;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Vote extends BaseCommand
{
    private static string $key = "DA0vitb5xERmoP1HwZ15QkzfI8B8Igcha5";

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "vote",
            "Vote sur le serveur pour récuperer des récompenses"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public static function getResult(string $name, string $type, ?int $result): void
    {
        $player = Main::getInstance()->getServer()->getPlayerExact($name);

        if (!$player instanceof Player) {
            return;
        }

        if ($type === "object") {
            if ($result === 1) {
                self::sendPlayer($player, "action");
            } else {
                $message = match ($result) {
                    0 => "Vous n'avez toujours pas voté sur le serveur, rendez vous sur §chttps://emptyfac.com/vote§f, pour pouvoir voté !",
                    default => "Vous avez déjà voté dans les 24 dernières heures"
                };

                $player->sendMessage(Util::PREFIX . $message);
            }
        } else if ($type === "action") {
            if ($result === 1) {
                self::sendVoteReward($player);
            } else {
                $player->sendMessage(Util::PREFIX . "Vous avez déjà voté les 24 dernières heures");
            }
        }
    }

    public static function sendPlayer(Player $player, string $type = "object"): void
    {
        $user = str_replace(" ", "_", $player->getName());
        $api = "https://minecraftpocket-servers.com/api/?";

        $query = match ($type) {
            "object" => "object=votes&element=claim&key=" . self::$key . "&username=" . $user,
            "action" => "action=post&object=votes&element=claim&key=" . self::$key . "&username=" . $user,
            default => null
        };

        if (!is_null($query)) {
            Main::getInstance()->getServer()->getAsyncPool()->submitTask(new VoteRequestTask($player->getName(), $type, $api . $query));
        }
    }

    public static function sendVoteReward(Player $player): void
    {
        Cache::$data["voteparty"] = (Cache::$data["voteparty"] ?? 0) + 1;

        if (intval(Cache::$data["voteparty"]) >= 150) {
            $keys = mt_rand(1, 2);

            foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $target) {
                Util::addItem($target, Box::createKeyItem("vote", $keys));
                $target->sendTitle("§c+ VoteParty +", "§c" . $keys . " §fclés §l§cVOTE §r§fvous ont été donnés");
            }

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le voteparty est arrivé à son maximum ! Vous venez tous de recevoir.. §c" . $keys . " §fclés §l§cVOTE §r§f! Profitez bien !");
            Cache::$data["voteparty"] = 0;
        }

        Util::addItem($player, Box::createKeyItem("vote", 2));

        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §c" . $player->getDisplayName() . " §fvient de recevoir §c2 §fclés §c§lVOTE §r§fcar il a voté sur §chttps://emptyfac.com/vote !");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("vote")) {
                $seconds = $session->getCooldownData("vote")[0] - time();
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez actuellement pas faire de /vote, merci d'attendre §c" . $seconds . " §fsecondes !");
            }

            self::sendPlayer($sender);
            $session->setCooldown("vote", 60);
        }
    }

    protected function prepare(): void
    {
    }
}