<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace Faction\handler;

use Faction\entity\LogoutNpc;
use Faction\Main;
use Faction\Util;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use WeakMap;

class Cache
{
    use SingletonTrait;

    public static array $players;
    public static array $data;
    public static array $config;
    public static array $market;
    public static array $bans;
    public static array $claims;
    public static array $factions;
    public static array $durability;

    public static array $deathXp = [];
    public static array $condenseShapes = [];
    public static array $pendingAlly = [];

    /* @var array<string, LogoutNpc> */
    public static array $logouts;

    /* @var WeakMap<Player, boolean> */
    public static WeakMap $scoreboardPlayers;
    /* @var WeakMap<Player, boolean> */
    public static WeakMap $borderPlayers;
    /* @var WeakMap<Player, boolean> */
    public static WeakMap $combatPlayers;

    public function __construct()
    {
        $this->setInstance($this);

        self::$scoreboardPlayers ??= new WeakMap();
        self::$borderPlayers ??= new WeakMap();
        self::$combatPlayers ??= new WeakMap();

        @mkdir(Main::getInstance()->getDataFolder() . "data/");
        @mkdir(Main::getInstance()->getDataFolder() . "data/players");
        @mkdir(Main::getInstance()->getDataFolder() . "data/inventories/");

        Main::getInstance()->saveResource("config.json", true);

        $this->condenseShapes();

        self::$config = Util::getFile("config")->getAll();
        self::$data = Util::getFile("data/data")->getAll();
        self::$market = Util::getFile("data/market")->getAll();
        self::$bans = Util::getFile("data/bans")->getAll();
        self::$claims = Util::getFile("data/claims")->getAll();
        self::$factions = Util::getFile("data/factions")->getAll();
        self::$durability = Util::getFile("data/durability")->getAll();

        foreach (Util::listAllFiles(Main::getInstance()->getDataFolder() . "data/players") as $file) {
            $path = pathinfo($file);
            $username = $path["filename"];

            $file = Util::getFile("data/players/" . $username);

            self::$players["money"][$username] = $file->get("money", 0);
            self::$players["kill"][$username] = $file->get("kill", 0);
            self::$players["death"][$username] = $file->get("death", 0);
            self::$players["killstreak"][$username] = $file->get("killstreak", 0);
            self::$players["played_time"][$username] = $file->get("played_time", 0);
            self::$players["upper_name"][strtolower($username)] = $file->get("upper_name", $username);

            foreach (Cache::$config["saves"] as $column) {
                self::$players[$column][$username] = $file->get($column, []);
            }
        }
    }

    private function condenseShapes(): void
    {
        $craftMgr = Main::getInstance()->getServer()->getCraftingManager();

        $array1 = array_fill(0, 3, "AAA");
        $array2 = array_fill(0, 2, "AA");

        foreach ($craftMgr->getShapedRecipes() as $recipes) {
            foreach ($recipes as $recipe) {
                if ($recipe->getShape() === $array1 || $recipe->getShape() === $array2) {
                    $output = $recipe->getResults()[0];
                    $input = null;

                    $ingredients = $recipe->getIngredientList();
                    $ingredient = $ingredients[0];

                    if ($ingredient instanceof MetaWildcardRecipeIngredient) {
                        $input = StringToItemParser::getInstance()->parse($ingredient->getItemId());

                        if (is_null($input)) {
                            continue;
                        }
                    } else if ($ingredient instanceof ExactRecipeIngredient) {
                        $input = $ingredient->getItem();
                    }

                    if (is_null($input)) {
                        continue;
                    }

                    self::$condenseShapes[] = [
                        "input" => $input,
                        "output" => $output,
                        "count" => count($ingredients)
                    ];
                }
            }
        }
    }

    public function saveAll(): void
    {
        $this->save(self::$data, "data");
        $this->save(self::$market, "market");
        $this->save(self::$bans, "bans");
        $this->save(self::$claims, "claims");
        $this->save(self::$factions, "factions");
        $this->save(self::$durability, "durability");
    }

    private function save(array $array, string $file): void
    {
        $file = Util::getFile("data/" . $file);

        $file->setAll($array);
        $file->save();
    }
}