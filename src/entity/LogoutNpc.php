<?php

namespace Faction\entity;

use Faction\command\staff\LastInventory;
use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;

class LogoutNpc extends Human
{
    public bool $killed = false;

    public ?string $player = null;
    private ?string $faction = null;

    private int $time = 600;

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if (
            $this->closed ||
            $this->player === null ||
            (Main::getInstance()->getServer()->getPlayerExact($this->player)) !== null ||
            $this->time-- <= 0
        ) {
            $this->killed = true;
            $this->time = 0;

            $this->close();
            return false;
        }

        $this->setNameTag("§7" . $this->player . " [" . $this->getHealth() . " §c❤§7] " . Util::formatDurationFromSeconds($this->time / 20, 1));
        return parent::entityBaseTick($tickDiff);
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($this->killed || $this->time <= 0) {
            return;
        }

        $damager = null;

        if (!is_null($this->faction) && !Faction::exists($this->faction)) {
            $this->faction = null;
        }

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if ($damager instanceof Player && Faction::hasFaction($damager) && Session::get($damager)->data["faction"] === $this->faction) {
                $source->cancel();
                return;
            }
        }

        if ($this->getHealth() >= $source->getFinalDamage()) {
            parent::attack($source);
            return;
        }

        if ($damager instanceof Player) {
            $session = Session::get($damager);

            $pot1 = Util::getInventoryItemCount($this->getInventory(), VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));
            $pot2 = Util::getItemCount($damager, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));

            Main::getInstance()->getLogger()->info($this->player . " (PNJ) a été tué par " . $damager->getDisplayName() . " (" . $damager->getName() . ")");
            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $this->player . "[§7" . $pot1 . "§c] §fa été tué par le joueur §c" . $damager->getDisplayName() . "[§7" . $pot2 . "§c]");

            $session->addValue("kill");
            $session->addValue("killstreak");

            if (Faction::hasFaction($damager)) Faction::addPower($session->data["faction"], 6);
            if (!is_null($this->faction)) Faction::addPower($this->faction, -4);

            if ($session->data["killstreak"] % 5 == 0) {
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "Le joueur §c" . $damager->getName() . " §fa fait §c" . $session->data["killstreak"] . " §fkill sans mourrir !");
            }
        } else {
            Main::getInstance()->getLogger()->info($this->player . " (PNJ) est mort (" . $source->getCause() . ")");
        }

        $file = Util::getFile("data/players/" . strtolower($this->player));
        $damager = ($damager instanceof Player) ? $damager : null;

        LastInventory::saveInventory($this->player, $damager, $this->saveNBT(), $this->getXpManager()->getCurrentTotalXp(), $file->get("killstreak"));

        $file->set("death", $file->get("death", 0) + 1);
        $file->set("killstreak", 0);
        $file->save();

        $nbt = Main::getInstance()->getServer()->getOfflinePlayerData($this->player);
        $spawn = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation();

        $nbt->setString("Level", $spawn->getWorld()->getFolderName());
        $nbt->setTag("Inventory", new ListTag([], NBT::TAG_Compound));
        $nbt->setTag("ActiveEffects", new ListTag([], NBT::TAG_Compound));
        $nbt->setFloat("Health", 20);
        $nbt->setInt("XpLevel", 0);

        $nbt->setTag("Pos", new ListTag([
            new DoubleTag($spawn->getFloorX()),
            new DoubleTag($spawn->getFloorY()),
            new DoubleTag($spawn->getFloorZ())
        ], NBT::TAG_Double));

        Main::getInstance()->getServer()->saveOfflinePlayerData($this->player, $nbt);

        $this->onDeath();
        $this->killed = true;
    }

    public function initEntityB(Player $player): void
    {
        Faction::hasFaction($player);

        $this->player = $player->getName();
        $this->faction = Session::get($player)->data["faction"];

        Cache::$logouts[$this->player] = $this;

        $this->getInventory()->setContents($player->getInventory()->getContents());
        $this->getArmorInventory()->setContents($player->getArmorInventory()->getContents());

        $this->getXpManager()->setCurrentTotalXp($player->getXpManager()->getCurrentTotalXp());
    }
}