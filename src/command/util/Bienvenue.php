<?php /** @noinspection PhpUnused */

namespace Faction\command\util;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\Rank;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Bienvenue extends BaseCommand
{
    public static array $alreadyWished = [];
    public static string $lastJoin = "";

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "bienvenue",
            "Souhaite la bienvenue à un nouveau joueur"
        );

        $this->setAliases(["bvn"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (Bienvenue::$lastJoin === "" || in_array($sender->getName(), Bienvenue::$alreadyWished)) {
                $sender->sendMessage(Util::PREFIX . "Vous avez déjà souhaité la bienvenue ou aucun nouveau joueur n'a rejoint le serveur dernièrement");
                return;
            }

            $message = str_replace("{player}", Bienvenue::$lastJoin, Cache::$config["welcome-messages"][array_rand(Cache::$config["welcome-messages"])]);

            Bienvenue::$alreadyWished[] = $sender->getName();
            $session->addValue("money", 500);

            $sender->chat($message);
            $sender->sendMessage(Util::PREFIX . "Vous avez reçu §c500$ §fcar vous avez souhaité la bienvenue de §c" . Bienvenue::$lastJoin);
        }
    }

    protected function prepare(): void
    {
    }
}