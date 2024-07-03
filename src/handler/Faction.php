<?php

namespace Faction\handler;

use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\block\Barrel;
use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;

class Faction
{
    const MAP_KEY_CHARS = "\\/#?ç¬£$%=&^ABCDEFGHJKLMNOPQRSTUVWXYZÄÖÜÆØÅ1234567890abcdeghjmnopqrsuvwxyÿzäöüæøåâêîûô";

    const DIRECTIONS = [
        "N" => "N",
        "NE" => "/",
        "E" => "E",
        "SE" => "\\",
        "S" => "S",
        "SO" => "/",
        "O" => "O",
        "NO" => "\\",
        "NONE" => "+"
    ];

    public static function getNextRank(string $rank): string
    {
        $ranks = array_keys(Cache::$config["faction-ranks"]);
        return $ranks[self::getRankPosition($rank) - 1] ?? $rank;
    }

    public static function getRankPosition(string $rank): int
    {
        $ranks = array_keys(Cache::$config["faction-ranks"]);
        return array_search($rank, $ranks);
    }

    public static function getPreviousRank(string $rank): string
    {
        $ranks = array_keys(Cache::$config["faction-ranks"]);
        return $ranks[self::getRankPosition($rank) + 1] ?? $rank;
    }

    public static function broadcastFactionMessage(string $faction, string $message, bool $ally = false): void
    {
        if ($ally) {
            $prefix = "§c[§fALLY§c] [§f" . $faction . "§c] §f";
        } else {
            $prefix = "§c[§fFAC§c] §f";
        }

        $members = self::getFactionMembers($faction, true);

        foreach ($members as $player) {
            if ($player instanceof Player) {
                $player->sendMessage($prefix . $message);
            }
        }

        if ($ally) {
            self::broadcastAllyMessage($faction, $message);
        }
    }

    public static function getFactionMembers(string $key, bool $online): array
    {
        $arr = [];

        if (isset(Cache::$factions[$key])) {
            $list = Cache::$factions[$key]["members"];
            $leader = $list["leader"];

            if ($online) {
                /** @noinspection PhpDeprecationInspection */
                $leader = Main::getInstance()->getServer()->getPlayerByPrefix($leader);

                if ($leader instanceof Player) {
                    $arr[] = $leader;
                }
            } else {
                $arr[] = $leader;
            }
            $members = array_merge($list["officiers"], $list["members"], $list["recruits"]);

            foreach ($members as $player) {
                if ($online) {
                    /** @noinspection PhpDeprecationInspection */
                    $player = Main::getInstance()->getServer()->getPlayerByPrefix($player);

                    if ($player instanceof Player) {
                        $arr[] = $player;
                    }
                } else {
                    $arr[] = $player;
                }
            }
        }
        return $arr;
    }

    private static function broadcastAllyMessage(string $faction, string $message): void
    {
        $prefix = "§c[§fALLY§c] [§f" . $faction . "§c] §f";
        $ally = self::getAlly($faction);

        if (is_null($ally)) {
            return;
        }

        $members = self::getFactionMembers($ally, true);

        foreach ($members as $player) {
            if ($player instanceof Player) {
                $player->sendMessage($prefix . $message);
            }
        }
    }

    public static function getAlly(?string $faction): ?string
    {
        return Cache::$factions[strtolower($faction ?? "")]["ally"] ?? null;
    }

    public static function getFactionUpperName(string $faction): string
    {
        return !isset(Cache::$factions[$faction]) ? $faction : Cache::$factions[$faction]["upper_name"];
    }

    public static function addPower(string $faction, int $amount): void
    {
        self::setPower($faction, $amount + self::getPower($faction));

        if (self::getPower($faction) < 0) {
            self::setPower($faction, 0);
        }
    }

    private static function setPower(string $faction, int $amount): void
    {
        Cache::$factions[$faction]["power"] = $amount;
    }

    public static function getPower(string $faction): int
    {
        return Cache::$factions[$faction]["power"];
    }

    public static function canBuild(Player $player, Block|Position $block, string $type): bool
    {
        $session = Session::get($player);
        $faction = $session->data["faction"];

        $position = $block instanceof Position ? $block : $block->getPosition();

        if ($player->getGamemode() === GameMode::CREATIVE() && $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            return true;
        } else if (Util::insideZone($position, "spawn") || Util::insideZone($position, "warzone")) {
            return false;
        } else {
            $claim = Faction::inClaim($position->getX(), $position->getZ());

            if ($claim[0]) {
                if ($type === "interact") {
                    $type = match (true) {
                        $block instanceof Door => "door",
                        $block instanceof Trapdoor => "trapdoor",
                        $block instanceof FenceGate => "fence-gates",
                        $block instanceof Chest, $block instanceof Barrel => "chest",
                        default => null
                    };
                } else if ($type === "break" && ($block instanceof Chest || $block instanceof Barrel)) {
                    $type = "chest";
                }

                $permission = is_null($type) ? true : Faction::hasPermission($player, $type);

                if (is_bool($permission)) {
                    if ($type === "interact" || $permission) {
                        return $claim[1] == $faction;
                    }
                }
            }
        }

        return true;
    }

    public static function hasPermission(Player $player, string $permission): ?bool
    {
        $session = Session::get($player);
        $rank = self::getFactionRank($player);

        if (is_null($rank)) {
            return null;
        }

        $faction = $session->data["faction"];
        $data = Cache::$factions[$faction];

        if ($rank !== "leader") {
            $require = $data["permissions"][$permission] ?? null;

            if (is_null($require)) {
                return true;
            }

            $passed = false;

            if ($rank === $require) {
                return true;
            }

            foreach (array_keys(Cache::$config["faction-ranks"]) as $value) {
                if (!$passed && $value === $require) {
                    return false;
                } else if ($rank === $value) {
                    $passed = true;
                }
            }
        }
        return true;
    }

    public static function getFactionRank(string|Player $key, string $value = null): ?string
    {
        $session = null;

        if ($key instanceof Player) {
            $session = Session::get($key);
            $value = $key->getName();

            $key = $session->data["faction"];
        }

        if (!self::exists($key)) {
            if ($session instanceof Session) {
                $session->data["faction"] = null;
                $session->data["faction_chat"] = false;
            }
            return null;
        }

        $members = Cache::$factions[$key]["members"];

        if ($members["leader"] === $value) {
            return "leader";
        } else if (in_array($value, $members["officiers"])) {
            return "officier";
        } else if (in_array($value, $members["members"])) {
            return "member";
        } else if (in_array($value, $members["recruits"])) {
            return "recruit";
        } else {
            if ($session instanceof Session) {
                $session->data["faction"] = null;
                $session->data["faction_chat"] = false;
            }
            return null;
        }
    }

    public static function exists(?string $key): bool
    {
        return !is_null($key) && isset(Cache::$factions[strtolower($key)]);
    }

    public static function inClaim(int|float $x, int|float $z): array
    {
        $chunkX = intval(floor($x)) >> Chunk::COORD_BIT_SIZE;
        $chunkZ = intval(floor($z)) >> Chunk::COORD_BIT_SIZE;

        return self::inClaimByChunk($chunkX, $chunkZ);
    }

    private static function inClaimByChunk(int $chunkX, int $chunkZ): array
    {
        $chunk = $chunkX . ":" . $chunkZ;

        if (isset(Cache::$claims[$chunk])) {
            // In claim, faction, chunk name
            return [true, Cache::$claims[$chunk], $chunk];
        } else {
            return [false, null, $chunk];
        }
    }

    public static function setAlly(string $faction, ?string $ally): void
    {
        Cache::$factions[strtolower($faction)]["ally"] = $ally;
    }

    public static function getMap(Player $player): array
    {
        $centerChunkX = $player->getPosition()->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $centerChunkZ = $player->getPosition()->getFloorZ() >> Chunk::COORD_BIT_SIZE;

        $height = 10;
        $width = 48;

        $compass = self::getAsciiCompass($player);
        $header = "§7------------------ §c[ §7" . $centerChunkX . "§c, §7" . $centerChunkZ . " §c] §7------------------";

        $map = [$header];

        $legend = [];
        $characterIndex = 0;
        $overflown = false;

        for ($dz = 0; $dz < $height; $dz++) {
            $row = "";

            for ($dx = 0; $dx < $width; $dx++) {
                $chunkX = $centerChunkX - ($width / 2) + $dx;
                $chunkZ = $centerChunkZ - ($height / 2) + $dz;

                if ($chunkX === $centerChunkX && $chunkZ === $centerChunkZ) {
                    $row .= "§b+";
                    continue;
                }

                $data = self::inClaimByChunk($chunkX, $chunkZ);

                if ($data[0]) {
                    $faction = $data[1];

                    if (($symbol = array_search($faction, $legend)) === false && $overflown) {
                        $row .= "§f-§r";
                    } else {
                        if ($symbol === false) {
                            $legend[($symbol = self::MAP_KEY_CHARS[$characterIndex++])] = $faction;
                        }
                        if ($characterIndex === strlen(self::MAP_KEY_CHARS)) {
                            $overflown = true;
                        }

                        $row .= self::getMapColor($player, $faction) . $symbol;
                    }
                } else {
                    $row .= "§7-";
                }
            }

            if ($dz <= 2) {
                $row = $compass[$dz] . substr($row, 3 * strlen("§b+"));
            }

            $map[] = $row;
        }

        $map[] = implode(" ", array_map(function (string $character, $faction) use ($player): string {
            return self::getMapColor($player, $faction) . $character . " §f: " . $faction;
        }, array_keys($legend), $legend));

        if ($overflown) {
            $map[] = "§f-§r" . "§cTrop de factions (>86) sur la carte";
        }

        return $map;
    }

    private static function getAsciiCompass(Player $player): array
    {
        $degrees = $player->getLocation()->getYaw();

        $rows = [["NO", "N", "NE"], ["O", "NONE", "E"], ["SO", "S", "SE"]];
        $direction = self::getDirectionsByDegrees($degrees);

        return array_map(function (array $directions) use ($direction): string {
            $row = "";

            foreach ($directions as $d) {
                $row .= ($direction === $d ? "§a" : "§c") . self::DIRECTIONS[$d];
            }
            return $row;
        }, $rows);
    }

    public static function getDirectionsByDegrees(float $degrees): string
    {
        $degrees = (round($degrees) - 157) % 360;
        if ($degrees < 0) $degrees += 360;

        return array_keys(self::DIRECTIONS)[floor($degrees / 45)];
    }

    public static function getMapColor(Player $player, string $faction): string
    {
        if (!self::hasFaction($player)) {
            return "§c";
        }

        $playerFaction = Session::get($player)->data["faction"];

        if ($faction === $playerFaction) {
            return "§a";
        } else if (Faction::getAlly($playerFaction) === $faction) {
            return "§e";
        }

        return "§c";
    }

    public static function hasFaction(Player $player): bool
    {
        self::getFactionRank($player);
        return !is_null(Session::get($player)->data["faction"]);
    }

    public static function isFreekill(Player $victim, Player $killer): bool
    {
        $lastKills = Session::get($killer)->data["last_kills"];
        $count = count($lastKills);

        if ($count < 3) {
            return false;
        }

        $lastThreeKills = array_slice($lastKills, -3);

        return count(array_filter($lastThreeKills, function ($kill) use ($victim) {
                return $kill === $victim->getName();
            })) >= 3;
    }

    public static function addFreekill(Player $victim, Player $killer): void
    {
        Session::get($killer)->data["last_kills"][] = $victim->getName();
    }
}