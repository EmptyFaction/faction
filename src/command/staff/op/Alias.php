<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\Rank;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class Alias extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "alias",
            "Permet de voir tous les comptes d'un joueur"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = strtolower($args["joueur"]);

        if (!isset(Cache::$players["upper_name"][$target])) {
            $sender->sendMessage(Util::PREFIX . "Ce joueur ne s'est jamais connecté au serveur (verifiez bien les caractères)");
            return;
        }

        $alias = $this->getPlayerAliasByName($target);
        $bar = "§l§8-----------------------";

        if (count($alias) === 0) {
            $sender->sendMessage(Util::PREFIX . "Le joueur §c" . $target . " §fne possède aucun double compte lié à son ip, did etc...");
            return;
        }

        $sender->sendMessage($bar);
        $sender->sendMessage(Util::PREFIX . "Liste de compte lié au compte §c" . $target);

        foreach ($alias as $username) {
            $sender->sendMessage("§f- §c" . $username);
        }

        $sender->sendMessage($bar);
    }

    private function getPlayerAliasByName(string $name): array
    {
        $file = Util::getFile("data/players/" . $name);
        $result = [];

        foreach (Cache::$config["saves"] as $column) {
            $ip = $file->get($column, []);

            foreach (Cache::$players[$column] as $key => $value) {
                $similar = array_intersect_assoc($value, $ip);

                if (count($similar) > 0 && $key !== $name) {
                    $result[] = $key . " §f- Depuis son §c" . $column;
                }
            }
        }
        return $result;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}