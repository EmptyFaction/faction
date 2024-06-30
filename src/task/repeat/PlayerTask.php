<?php

namespace Faction\task\repeat;

use Faction\command\staff\op\Clearlag;
use Faction\handler\Cache;
use Faction\handler\ScoreFactory;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use WeakMap;

class PlayerTask extends Task
{
    /* @var WeakMap<Player, Vector3> */
    private static WeakMap $lastPosition;

    private int $tick = 0;
    private static int $clearlag = 301;

    public function __construct()
    {
        self::$lastPosition = new WeakMap();
    }

    public function onRun(): void
    {
        $this->tick++;
        self::$clearlag--;

        KothTask::run();
        OutpostTask::run();

        if ($this->tick % 3 == 0) {
            MoneyZoneTask::run();
        }

        if (self::$clearlag % 60 === 0 || 10 > self::$clearlag || self::$clearlag === 30 || self::$clearlag === 15) {
            if (self::$clearlag === 0) {
                Clearlag::clearlag();
            } else if (11 > self::$clearlag) {
                Main::getInstance()->getServer()->broadcastTip(Util::ARROW . "ClearLag dans §c" . self::$clearlag . " §fseconde(s)" . Util::IARROW);
            } else {
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le prochain clearlag aura lieu dans §c" . Util::formatDurationFromSeconds(self::$clearlag));
            }
        }

        if ($this->tick % 250 == 0) {
            $messages = Cache::$config["messages"];
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . $messages[array_rand($messages)]);
        }

        foreach (Cache::$combatPlayers as $player => $ignore) {
            if (!$player instanceof Player || !$player->isConnected()) {
                continue;
            }

            $session = Session::get($player);
            $position = $player->getPosition();

            if ($session->inCooldown("combat")) {
                if ($player->getWorld() !== Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
                    continue;
                }

                if (Util::insideZone($position, "spawn")) {
                    if (isset(self::$lastPosition[$player])) {
                        $player->teleport(self::$lastPosition[$player]);
                    }
                }
            } else {
                $player->sendMessage(Util::PREFIX . "Vous n'êtes désormais plus en combat");
                unset(Cache::$combatPlayers[$player]);
            }

            if (!Util::insideZone($position, "spawn")) {
                self::$lastPosition[$player] = $position->asVector3();
            }
        }

        foreach (Cache::$borderPlayers as $player => $ignore) {
            if ($player->isConnected() && $this->tick % 3 == 0) {
                Util::addBorderParticles($player);
            }
        }

        foreach (Cache::$scoreboardPlayers as $player => $ignore) {
            if ($player->isConnected() && $this->tick % 45 == 0) {
                ScoreFactory::updateScoreboard($player);
            }
        }

        foreach (Cache::$config["interval"] as $tick => $command) {
            if ($this->tick % intval($tick) == 0) {
                Util::executeCommand($command);
            }
        }

        if ($this->tick % 50 == 0) {
            $time = date("H:i");

            if (isset(Cache::$config["planning"][$time])) {
                Util::executeCommand(Cache::$config["planning"][$time]);
            }

            if (($h = intval(explode(":", $time)[0])) >= 12 && $h <= 24) {
                if (intval(explode(":", $time)[1]) === 0 && ($h - 13) % 2 === 0) {
                    Util::executeCommand("nexus start");
                } else if (intval(explode(":", $time)[1]) === 30) {
                    Util::executeCommand("koth start");
                }
            }
        }
    }

    public static function getNextClearlag(): int
    {
        return self::$clearlag;
    }

    public static function resetClearlag(): void
    {
        self::$clearlag = 301;
    }
}