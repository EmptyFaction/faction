<?php

namespace Faction;

use Faction\command\player\XpBottle;
use Faction\command\util\money\Money;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\handler\Jobs;
use Faction\handler\Rank;
use Faction\handler\ScoreFactory;
use Faction\item\ExtraVanillaItems;
use pocketmine\color\Color;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\java\GameModeIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\inventory\Inventory;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\player\PlayerDataLoadException;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Binary;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\Position;
use Symfony\Component\Filesystem\Path;

class Util
{
    const PREFIX = "§c[§l§f!§r§c] §f";

    const ARROW = "§l§c» §r§f";
    const IARROW = " §l§c«";

    public static function arrayToPage(array $array, ?int $page, int $separator): array
    {
        $result = [];

        $pageMax = ceil(count($array) / $separator);
        $min = ($page * $separator) - $separator;

        $count = 0;
        $max = $min + $separator;

        foreach ($array as $key => $value) {
            if ($count >= $max) {
                break;
            } else if ($count >= $min) {
                $result[$key] = $value;
            }

            $count++;
        }

        return [$pageMax, $result];
    }

    public static function findPlayerByName(string $name): ?string
    {
        /** @noinspection PhpDeprecationInspection */
        $player = Main::getInstance()->getServer()->getPlayerByPrefix($name);

        if ($player instanceof Player) {
            $name = $player->getName();
        } else {
            $name = strtolower($name);

            if (!isset(Cache::$players["upper_name"][$name])) {
                return null;
            }
        }
        return $name;
    }

    public static function allSelectorExecute(CommandSender $sender, string $command, array $args): void
    {
        if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $sender->sendMessage(self::PREFIX . "Vous n'avez pas la permission de faire cela");
            return;
        }

        foreach (Main::getInstance()->getServer()->getOnlinePlayers() as $player) {
            $cmd = $command . " " . implode(" ", $args);
            $cmd = str_replace("@a", "\"" . $player->getName() . "\"", $cmd);

            self::executeCommand($cmd);
        }
    }

    public static function executeCommand(string $command): void
    {
        $server = Main::getInstance()->getServer();
        $server->dispatchCommand(new ConsoleCommandSender($server, $server->getLanguage()), $command);
    }

    public static function addValue(string $staff, string $player, array|string $path, int $value, bool $substraction = false): void
    {
        $player = Main::getInstance()->getServer()->getPlayerExact($player);

        if ($player instanceof Player) {
            Session::get($player)->addValue($path, $value, $substraction);
            $word = is_string($path) ? $path : implode(" ", $path);

            $player->sendMessage(
                $substraction ?
                    self::PREFIX . "Le staff §c" . $staff . " §fvient de vous retirer §c" . $value . " " . $word :
                    self::PREFIX . "Le staff §c" . $staff . " §fvient de vous ajouter §c" . $value . " " . $word
            );
        } else {
            $file = self::getFile("data/players/" . $player);
            $data = self::addArrayValue($file->getAll(), $path, $value);

            if (is_string($path) && isset(Cache::$players[$path])) {
                Cache::$players[$path][$player] = $file->get($path) + $value;
            }

            if ($file->getAll() !== []) {
                $file->setAll($data);
                $file->save();
            }
        }
    }

    public static function getFile($name): Config
    {
        return new Config(Main::getInstance()->getDataFolder() . $name . ".json", Config::JSON);
    }

    public static function addArrayValue(array $data, array|string $path, int $value, bool $substraction = false): array
    {
        $path = is_string($path) ? [$path] : $path;
        $lastPart = array_pop($path);

        $current = &$data;

        foreach ($path as $part) {
            if (!isset($current[$part])) {
                $current[$part] = [];
            }

            $current = &$current[$part];
        }

        $current[$lastPart] = ($substraction ? ($current[$lastPart] ?? 0) - $value : ($current[$lastPart] ?? 0) + $value);
        return $data;
    }

    public static function getItemCount(Player $player, Item $item): int
    {
        return self::getInventoryItemCount($player->getInventory(), $item);
    }

    public static function getInventoryItemCount(Inventory $inventory, Item $item): int
    {
        $count = 0;

        foreach ($inventory->getContents() as $_item) {
            if ($_item->equals($item, true, false)) {
                $count += $_item->getCount();
            }
        }
        return $count;
    }

    public static function givePlayerPreferences(Player $player): void
    {
        $data = Session::get($player)->data;

        if ($data["night_vision"]) {
            $player->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 20 * 60 * 60 * 24, 255, false));
        }

        $player->setViewDistance(8);

        $pk = new GameRulesChangedPacket();
        $pk->gameRules = ["showcoordinates" => new BoolGameRule($data["coordinates"], false)];
        $player->getNetworkSession()->sendDataPacket($pk);

        if ($data["border"]) {
            Cache::$borderPlayers[$player] = true;
        }

        if ($data["faction_map"]) {
            Cache::$factionMapPlayers[$player] = true;
        }

        if ($data["scoreboard"]) {
            Cache::$scoreboardPlayers[$player] = true;
            ScoreFactory::updateScoreboard($player);
        }

        if ($data["staff_mod"][0] && $player->getGamemode() === GameMode::SURVIVAL()) {
            $player->setAllowFlight(true);
        }

        foreach ($player->getArmorInventory()->getContents() as $item) {
            ExtraVanillaItems::getItem($item)->addEffects($player->getArmorInventory(), $item);
        }
    }

    public static function updateNametag(Player $player): void
    {
        $player->setScoreTag("§7" . round($player->getHealth(), 2) . " §c❤");
    }

    public static function formatNumberWithSuffix(int|float $value): string
    {
        $value = intval($value);
        $value = (0 + str_replace(",", "", $value));

        if ($value >= 1000000000000) {
            return round($value / 1000000000000, 2) . "MD";
        } else if ($value >= 1000000000) {
            return round($value / 1000000000, 2) . "B";
        } else if ($value >= 1000000) {
            return round($value / 1000000, 2) . "M";
        } else if ($value >= 1000) {
            return round($value / 1000, 2) . "k";
        }
        return number_format($value);
    }

    public static function getItemByName(string $name): Item
    {
        $item = StringToItemParser::getInstance()->parse("minecraft:" . $name);
        return $item instanceof Item ? $item : VanillaItems::AIR();
    }

    public static function savePlayerData(Player $player): string|null|bool
    {
        $data = $player->getSaveData();

        foreach (Cache::$config["clean-save-data"] as $name) {
            $data->removeTag($name);
        }

        return self::serializeCompoundTag($data);
    }

    public static function serializeCompoundTag(CompoundTag $tag): string
    {
        $nbt = new BigEndianNbtSerializer();
        return base64_encode(zlib_encode($nbt->write(new TreeRoot($tag)), ZLIB_ENCODING_DEFLATE));
    }

    public static function listAllFiles(string $dir): array
    {
        $array = scandir($dir);
        $result = [];

        foreach ($array as $value) {
            $currentPath = Path::join($dir, $value);

            if ($value === "." || $value === "..") {
                continue;
            } else if (is_file($currentPath)) {
                $result[] = $currentPath;
                continue;
            }

            foreach (self::listAllFiles($currentPath) as $_value) {
                $result[] = $_value;
            }
        }
        return $result;
    }

    public static function addBorderParticles(Player $player): void
    {
        $position = $player->getPosition()->asVector3();

        $chunkX = $position->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $position->getFloorZ() >> Chunk::COORD_BIT_SIZE;

        $claim = Faction::inClaim($player->getPosition()->getX(), $player->getPosition()->getZ());
        $canBuild = Faction::canBuild($player, $player->getPosition(), "interact");

        if ($claim[0]) {
            if ($canBuild) {
                $colors = [85, 255, 85]; // Own faction
            } else {
                $colors = [255, 85, 85]; // Enemies / Ally
            }
        } else {
            if (!$canBuild) {
                $colors = [255, 85, 255]; // Protected area
            } else {
                $colors = [0, 170, 0]; // Wilderness
            }
        }

        $minX = (float)$chunkX * 16;
        $maxX = $minX + 16;

        $minZ = (float)$chunkZ * 16;
        $maxZ = $minZ + 16;

        [$r, $g, $b] = $colors;
        $y = $position->getY();

        for ($i = 0; $i < 2; $i++) {
            for ($x = $minX; $x <= $maxX; $x += 0.5) {
                for ($z = $minZ; $z <= $maxZ; $z += 0.5) {
                    if ($x === $minX || $x === $maxX || $z === $minZ || $z === $maxZ) {
                        $vector = new Vector3($x, $y + $i + 0.8, $z);

                        if ($player->getWorld()->isLoaded() && $player->getWorld()->isInLoadedTerrain($vector)) {
                            $player->getWorld()->addParticle($vector, new DustParticle(new Color($r, $g, $b)), [$player]);
                        }
                    }
                }
            }
        }
    }

    public static function getPlace(Player $player): int
    {
        return floor($player->getPosition()->getX() + $player->getPosition()->getY() + $player->getPosition()->getZ());
    }

    public static function addItems(Player $player, array $items, bool $canDrop = true): void
    {
        foreach ($items as $item) {
            if ($item instanceof Armor) {
                if ($player->getArmorInventory()->getItem($item->getArmorSlot())->equals(VanillaItems::AIR())) {
                    $player->getArmorInventory()->setItem($item->getArmorSlot(), $item);
                    continue;
                }
            }

            self::addItem($player, $item, $canDrop);
        }
    }

    public static function addItem(Player $player, Item $item, bool $canDrop = true): void
    {
        if ($canDrop && !$player->getInventory()->canAddItem($item)) {
            $player->getWorld()->dropItem($player->getPosition()->asVector3(), $item);
        }

        $player->getInventory()->addItem($item);
    }

    public static function getTpTime(Player $player, string $type): int
    {
        $session = Session::get($player);

        if (
            !$player->isAlive() ||
            $player->isCreative() ||
            $session->data["staff_mod"][0] ||
            $player->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()
        ) {
            return -1;
        } else {
            if (self::insideZone($player->getPosition(), "spawn") && $type !== "tpa") {
                return -1;
            }

            $rank = Rank::getEqualRankBySession($session);
            return Rank::getRankValue($rank, "teleportation");
        }
    }

    public static function insideZone(Position $position, string $zone): bool
    {
        [$x1, $y1, $z1, $x2, $y2, $z2, $world] = explode(":", Cache::$config["zones"][$zone]);

        $minX = min($x1, $x2);
        $minY = min($y1, $y2);
        $minZ = min($z1, $z2);

        $maxX = max($x1, $x2);
        $maxY = max($y1, $y2);
        $maxZ = max($z1, $z2);

        $x = $position->getFloorX();
        $y = $position->getFloorY();
        $z = $position->getFloorZ();

        return $x >= $minX && $x <= $maxX && $y >= $minY && $y <= $maxY && $z >= $minZ && $z <= $maxZ && $position->getWorld()->getFolderName() === $world;
    }

    public static function reprocess(string $input): string
    {
        return strtolower(str_replace([" ", "minecraft:"], ["_", ""], trim($input)));
    }

    public static function removeCurrentWindow(Player $player): void
    {
        if (!is_null($player->getCurrentWindow())) {
            $player->removeCurrentWindow();
        }
    }

    public static function getBourse(): array
    {
        $data = Cache::$data["bourse"] ?? [];

        $bourse = [];
        $count = 0;

        if (count($data) == 0) {
            $data = self::resetBourse();
        }

        arsort($data);

        foreach ($data as $name => $selled) {
            $count = $count + 1;

            $sellPrice = $count * 2;
            $buyPrice = ceil($sellPrice * 1.3);

            $bourse[] = $name . ":" . Cache::$config["bourse"][$name] . ":" . $buyPrice . ":" . floor($sellPrice);
        }

        return $bourse;
    }

    public static function resetBourse(): array
    {
        Cache::$data["bourse"] = [];

        foreach (Cache::$config["bourse"] as $name => $value) {
            Cache::$data["bourse"][$name] = 0;
        }

        return Cache::$data["bourse"];
    }

    public static function restorePlayer(Player $player, string $nbt): void
    {
        $nbt = self::deserializePlayerData($player->getName(), $nbt);

        $gamemode = $nbt->getInt("playerGameType");
        $xpLevel = $nbt->getInt("XpLevel");
        $xpProgress = $nbt->getFloat("XpP");
        $litetimeXpTotal = $nbt->getInt("XpTotal");

        $inventory = self::readInventory($nbt);
        $armorInventory = self::readArmorInventory($nbt);
        $effects = self::readEffects($nbt);

        $player->setGamemode(GameModeIdMap::getInstance()->fromId($gamemode));
        $player->getXpManager()->setXpLevel($xpLevel);
        $player->getXpManager()->setXpProgress($xpProgress);
        $player->getXpManager()->setLifetimeTotalXp($litetimeXpTotal);

        $player->getInventory()->setContents($inventory);
        $player->getArmorInventory()->setContents($armorInventory);

        $player->getInventory()->setHeldItemIndex($nbt->getInt("SelectedInventorySlot"));
        $player->setHealth($nbt->getFloat("Health"));

        foreach ($effects as $effect) {
            $player->getEffects()->add($effect);
        }
    }

    public static function deserializePlayerData(string $identifier, string $contents): CompoundTag
    {
        try {
            return (new BigEndianNbtSerializer())->read(zlib_decode(base64_decode($contents)))->mustGetCompoundTag();
        } catch (NbtDataException $e) {
            throw new PlayerDataLoadException("Failed to decode NBT data for \"" . $identifier . "\": " . $e->getMessage(), 0, $e);
        }
    }

    public static function readInventory(CompoundTag $nbt): array
    {
        $_ = [];
        $inventory = [];

        self::readInventoryAndArmorInventory($nbt, $inventory, $_);
        return $inventory;
    }

    private static function readInventoryAndArmorInventory(CompoundTag $nbt, array &$inventory, array &$armor_inventory): void
    {
        $inventory = [];
        $armor_inventory = [];

        $tag = $nbt->getListTag("Inventory");

        if ($tag === null) {
            return;
        }

        /** @var CompoundTag $item */
        foreach ($tag->getIterator() as $item) {
            $slot = $item->getByte("Slot");

            /** @noinspection PhpStatementHasEmptyBodyInspection */
            if ($slot >= 0 && $slot < 9) {
                // old hotbar stuff
            } else if ($slot >= 100 && $slot < 104) {
                $armor_inventory[$slot - 100] = Item::nbtDeserialize($item);
            } else {
                $inventory[$slot - 9] = Item::nbtDeserialize($item);
            }
        }
    }

    public static function readArmorInventory(CompoundTag $nbt): array
    {
        $_ = [];
        $inventory = [];

        self::readInventoryAndArmorInventory($nbt, $_, $inventory);
        return $inventory;
    }

    public static function readEffects(CompoundTag $nbt): array
    {
        $effects = [];
        $tag = $nbt->getListTag("ActiveEffects");

        if ($tag !== null) {
            /** @var CompoundTag $effect */
            foreach ($tag->getIterator() as $effect) {
                $effects[] = new EffectInstance(
                    EffectIdMap::getInstance()->fromId($effect->getByte("Id")),
                    $effect->getInt("Duration"),
                    Binary::unsignByte(($effect->getByte("Amplifier"))),
                    $effect->getByte("ShowParticles"),
                    $effect->getByte("Ambient")
                );
            }
        }
        return $effects;
    }

    public static function formatDurationFromSeconds(float|int $seconds, int $type = 0): string
    {
        $seconds = intval($seconds);

        if ($seconds === -1) {
            return "Permanent";
        }

        $d = floor($seconds / (3600 * 24));
        $h = floor($seconds % (3600 * 24) / 3600);
        $m = floor($seconds % 3600 / 60);
        $s = floor($seconds % 60);

        $dDisplay = $d > 0 ? $d . ($type === 0 ? " jour" . ($d == 1 ? "" : "s") : "j") . ", " : "";
        $hDisplay = $h > 0 ? $h . ($type === 0 ? " heure" . ($h == 1 ? "" : "s") : "h") . ", " : "";
        $mDisplay = $m > 0 ? $m . ($type === 0 ? " minute" . ($m == 1 ? "" : "s") : "m") . ", " : "";
        $sDisplay = $s > 0 ? $s . ($type === 0 ? " seconde" . ($s == 1 ? "" : "s") : "s") . ", " : "";

        $format = rtrim($dDisplay . $hDisplay . $mDisplay . $sDisplay, ", ");

        if (substr_count($format, ",") > 0) {
            return preg_replace("~(.*)" . preg_quote(",", "~") . "~", "$1 et", $format, 1);
        } else {
            return $format;
        }
    }

    public static function stringToUnicode(string $title): string
    {
        $result = "";

        foreach (str_split(TextFormat::clean($title)) as $caracter) {
            $result .= self::caracterToUnicode($caracter) . " ";
        }
        return trim($result);
    }

    public static function formatToRomanNumber(int $integer): string
    {
        $romanNumber = "";

        $units = [
            "X" => 10, "IX" => 9,
            "V" => 5, "IV" => 4,
            "I" => 1
        ];

        foreach ($units as $unit => $value) {
            while ($integer >= $value) {
                $integer -= $value;
                $romanNumber .= $unit;
            }
        }
        return $romanNumber;
    }

    public static function caracterToUnicode(string $input): string
    {
        return Cache::$config["unicodes"][strtolower($input)] ?? " ";
    }

    public static function findAndRemoveValue(&$array, $value): void
    {
        $index = array_search($value, $array);

        if ($index !== false) {
            unset($array[$index]);
        }
    }

    public static function antiBlockGlitch(Player $player): void
    {
        $session = Session::get($player);
        $delay = round(7 * (max($player->getNetworkSession()->getPing(), 50) / 50));

        if (!$session->inCooldown("enderpearl")) {
            $session->setCooldown("enderpearl", ceil($delay / 20), [$player->getPosition()]);
        }

        $player->teleport($player->getPosition(), 180, -90);
        $position = $player->getPosition();

        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $position) {
            if ($player->isOnline() && $position instanceof Position && $position->isValid()) {
                $player->teleport($position, 180, -90);
            }
        }), $delay);
    }

    // item:itemName:count:customName:enchantId1,enchantLevel1;enchantId2,enchantLevel2
    public static function parseItem(string $data): Item
    {
        $data = explode(":", $data);
        $type = $data[0];

        switch ($type) {
            case "xp":
                return XpBottle::createXpBottle(intval($data[1]) ?? 1);
            case "money":
                return Money::createPaperMoney(intval($data[1]) ?? 1);
            case "boost":
                return Jobs::createBoostPaper(intval($data[1]) ?? 1, intval($data[1]) ?? 1);
            default:
                $item = StringToItemParser::getInstance()->parse($data[1]) ?? VanillaItems::AIR();
                $item = $item->setCount(intval($data[2] ?? 1));

                if (($data[3] ?? "") !== "") {
                    $item = $item->setCustomName($data[3]);
                }

                $enchants = $data[4] ?? "";

                if ($enchants !== "") {
                    foreach (explode(";", $enchants) as $enchant) {
                        $enchant = explode(",", $enchant);

                        $enchant = new EnchantmentInstance(
                            EnchantmentIdMap::getInstance()->fromId(intval($enchant[0] ?? 0)),
                            intval($enchant[1] ?? 1)
                        );

                        $item = $item->addEnchantment($enchant);
                    }
                }
                return $item;
        }
    }
}
