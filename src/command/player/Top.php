<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;

class Top extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "top",
            "Envoie la liste des meilleurs joueurs ou factions"
        );

        $this->setAliases(["classement"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $i = 1;

        $page = !isset($args["page"]) ? 1 : $args["page"];
        $format = "§7{COUNT}. §c{KEY} §8(§f{VALUE}§8)";

        $top = self::getTopByCategory($args["categorie"]);
        $response = Util::arrayToPage($top, $page, 10);

        $sender->sendMessage(Util::PREFIX . self::getTopName($args["categorie"]) . " §f(Page §c#" . $page . "§f/§c" . $response[0] . "§f)");

        foreach ($response[1] as $key => $value) {
            if ($args["categorie"] === "nerd") {
                $value = Util::formatDurationFromSeconds(intval($value));
            }

            $sender->sendMessage(str_replace(["{KEY}", "{VALUE}", "{COUNT}"], [$key, $value, (($page - 1) * 10) + $i], $format));
            $i++;
        }
    }

    public static function getTopByCategory(string $category): array
    {
        $leaderboard = [];
        $category = $category === "nerd" ? "played_time" : $category;

        if ($category === "faction") {
            foreach (Cache::$factions as $value) {
                $leaderboard[$value["upper_name"]] = $value["power"];
            }
        } else {
            $array = Cache::$players[$category] ?? [];

            foreach ($array as $key => $value) {
                $upper = Cache::$players["upper_name"][$key] ?? $key;
                $leaderboard[$upper] = $value;
            }
        }

        arsort($leaderboard);
        return $leaderboard;
    }

    public static function getTopName(string $category): string
    {
        return match ($category) {
            "killstreak" => "Joueurs avec les plus gros §ckillstreak",
            "faction" => "Faction avec le plus de §cpowers",
            "death" => "Joueurs ayant le plus de §cmorts",
            "money" => "Joueurs ayant le plus de §cpièces",
            "nerd" => "Joueurs ayant le plus de §cjoué",
            default => "Joueurs ayant le plus de §ckills"
        };
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("categorie", ["killstreak", "kill", "money", "death", "faction", "nerd"]));
        $this->registerArgument(1, new IntegerArgument("page", true));
    }
}