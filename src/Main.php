<?php

namespace Faction;

use CortexPE\Commando\PacketHooker;
use Faction\block\ExtraVanillaBlocks;
use Faction\command\Commands;
use Faction\entity\Entities;
use Faction\handler\Cache;
use Faction\handler\Rank;
use Faction\item\ExtraVanillaItems;
use Faction\listener\EventsListener;
use Faction\task\PlayerTask;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Main extends PluginBase
{
    use SingletonTrait;

    protected function onLoad(): void
    {
        date_default_timezone_set("Europe/Paris");
        self::setInstance($this);
    }

    protected function onEnable(): void
    {
        new Cache();

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        new Rank();
        new Commands();
        new Entities();

        new ExtraVanillaBlocks();
        new ExtraVanillaItems();

        $this->getScheduler()->scheduleRepeatingTask(new PlayerTask(), 20);
        $this->getServer()->getPluginManager()->registerEvents(new EventsListener(), $this);
    }

    public function getFile(): string
    {
        return parent::getFile();
    }

    protected function onDisable(): void
    {
        Cache::getInstance()->saveAll();

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            Session::get($player)->saveSessionData();
        }
    }
}
