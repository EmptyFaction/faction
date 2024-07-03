<?php

namespace Faction\handler;

use Faction\item\Item as ExtraItem;
use Faction\Main;
use Faction\Session;
use Faction\task\BoxAnimationTask;
use Faction\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\sound\ChestOpenSound;

class Box
{
    public static function getItems(string $box): array
    {
        $items = [];

        foreach (Cache::$config["box"][$box]["rewards"] as $data) {
            $items[$data[1]] = Util::parseItem($data[2]);
        }

        return $items;
    }

    public static function isBox(Block $block): bool
    {
        return !is_null(self::getBoxData($block));
    }

    public static function openPreviewBox(Player $player, Block $block): void
    {
        $data = self::getBoxData($block);

        if (is_null($data)) {
            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);

        $menu->setName("Box §c§l" . strtoupper($data["name"]));
        $menu->setListener(InvMenu::readonly());

        foreach (Cache::$config["box"][$data["name"]]["rewards"] as $data) {
            $item = Util::parseItem($data[2]);
            $item->setCustomName("§r§c" . $data[0] . "% §f- " . ucfirst($data[1]));
            $item->getNamedTag()->setInt("menu_item", 0);
            $menu->getInventory()->addItem($item);
        }

        $menu->send($player);
    }

    public static function openBox(Player $player, Block $block): void
    {
        $data = self::getBoxData($block);

        if (is_null($data)) {
            return;
        } else if (Session::get($player)->inCooldown("box_" . strtolower($data["name"]))) {
            $player->sendMessage(Util::PREFIX . "Veuillez attendre un peu avant de ré-ouvrir une box..");
            return;
        }

        $item = $player->getInventory()->getItemInHand();
        $box = self::getKeyBox($item);

        if (is_null($box) || strtolower($data["name"]) !== strtolower($box)) {
            $player->sendMessage(Util::PREFIX . "Vous devez avoir une clé de box §l§c" . strtoupper($data["name"]) . " §r§fdans votre main pour ouvrir cette box");
            return;
        }

        (new ExtraItem())->projectileSucces($player, $item);

        $player->sendMessage(Util::PREFIX . "Vous ouvrez une box §c§l" . strtoupper($data["name"]) . "§r§f...");
        $player->getWorld()->addSound($block->getPosition()->add(0.5, 0.5, 0.5), new ChestOpenSound());

        Main::getInstance()->getServer()->broadcastPopup(Util::ARROW . "§c" . $player->getName() . " §fouvre une box §c§l" . strtoupper($box) . Util::IARROW);
        Main::getInstance()->getScheduler()->scheduleRepeatingTask(new BoxAnimationTask($player, $block), 1);
    }

    public static function createKeyItem(string $box, int $count): Item
    {
        $item = VanillaBlocks::TRIPWIRE_HOOK()->asItem();
        $item->getNamedTag()->setString("key", strtolower($box));
        $item->setCustomName("§r§fClé de box §c§l" . strtoupper($box));
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FORTUNE()));
        return $item->setCount($count);
    }

    public static function getKeyBox(Item $item): ?string
    {
        return $item->getNamedTag()->getString("key", "unknown");
    }

    public static function getBoxData(Block $block): ?array
    {
        $position = $block->getPosition();

        foreach (Cache::$config["box"] as $name => $data) {
            $format = $position->getFloorX() . ":" . $position->getFloorY() . ":" . $position->getFloorZ();

            if ($data["pos"] === $format) {
                $data["name"] = $name;
                return $data;
            }
        }

        return null;
    }
}