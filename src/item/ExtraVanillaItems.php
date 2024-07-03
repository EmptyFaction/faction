<?php

namespace Faction\item;

use Faction\item\enchantment\Enchantments;
use Faction\Util;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item as PmItem;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class ExtraVanillaItems
{
    private static array $items = [];

    public function __construct()
    {
        self::addItem(VanillaItems::EXPERIENCE_BOTTLE(), new XpBottle());
        self::addItem(VanillaItems::ENDER_PEARL(), new EnderPearl());
        self::addItem(VanillaItems::FLINT_AND_STEEL(), new FlintAndSteal());
        self::addItem(VanillaItems::COMPASS(), new Compass());

        self::addItem(VanillaItems::CHAINMAIL_HELMET(), new TurtleArmor(408 * 0.2, 1, VanillaEffects::WATER_BREATHING(), 0));
        self::addItem(VanillaItems::CHAINMAIL_CHESTPLATE(), new TurtleArmor(593 * 0.2, 3, VanillaEffects::HASTE(), 0));
        self::addItem(VanillaItems::CHAINMAIL_LEGGINGS(), new TurtleArmor(556 * 0.2, 2, VanillaEffects::SPEED(), 2));
        self::addItem(VanillaItems::CHAINMAIL_BOOTS(), new TurtleArmor(442 * 0.2, 1, VanillaEffects::JUMP_BOOST(), 2));

        self::addItem(VanillaItems::GOLDEN_HELMET(), new Armor(408 * 1.3, 3));
        self::addItem(VanillaItems::GOLDEN_CHESTPLATE(), new Armor(593 * 1.3, 8));
        self::addItem(VanillaItems::GOLDEN_LEGGINGS(), new Armor(556 * 1.3, 7));
        self::addItem(VanillaItems::GOLDEN_BOOTS(), new Armor(442 * 1.3, 3));

        self::addItem(VanillaItems::NETHERITE_HELMET(), new Armor(408 * 1.7, 4));
        self::addItem(VanillaItems::NETHERITE_CHESTPLATE(), new Armor(593 * 1.7, 8));
        self::addItem(VanillaItems::NETHERITE_LEGGINGS(), new Armor(556 * 1.7, 7));
        self::addItem(VanillaItems::NETHERITE_BOOTS(), new Armor(442 * 1.7, 4));

        self::addItem(VanillaItems::IRON_AXE(), new UnclaimFinder());

        self::addItem(VanillaItems::GOLDEN_AXE(), new FarmAxe(2032 * 1.3, 15));
        self::addItem(VanillaItems::GOLDEN_PICKAXE(), new FarmAxe(2032 * 1.7, 17));

        self::addItem(VanillaItems::GOLDEN_SWORD(), new Sword(2032 * 1.3, 9));
        self::addItem(VanillaItems::NETHERITE_SWORD(), new Sword(2032 * 1.7, 10));

        self::addItem(VanillaItems::GOLDEN_HOE(), new WateringCan());
        self::addItem(VanillaItems::GOLDEN_SHOVEL(), new Fork());

        self::addItem(VanillaItems::NAUTILUS_SHELL(), new HangGlider());
        self::addItem(VanillaItems::IRON_SHOVEL(), new BoostedShovel());

        self::addItem(VanillaBlocks::MOB_HEAD()->asItem(), new EffectHead());

        self::addUnknowItem("rapid_fertilizer", new RapidFertilizer());
        self::addUnknowItem("creeper_spawn_egg", new CreeperEgg());

        new Craft();
        new Enchantments();
    }

    public static function addItem(PmItem $item, Item $replace): void
    {
        self::$items[Util::reprocess($item->getVanillaName())] = $replace;
    }

    private static function addUnknowItem(string $itemName, Item $replace): void
    {
        $item = StringToItemParser::getInstance()->parse($itemName) ?? null;

        if ($item instanceof PmItem) {
            self::addItem($item, $replace);
        }
    }

    public static function registerItem(string $id, PmItem $item, bool $first): void
    {
        if ($first) {
            GlobalItemDataHandlers::getDeserializer()->map($id, fn() => clone $item->clearCustomName());
            GlobalItemDataHandlers::getSerializer()->map($item->clearCustomName(), fn() => new SavedItemData($id));
        }

        StringToItemParser::getInstance()->override($id, fn() => clone $item);
        CreativeInventory::getInstance()->add($item);
    }

    public static function getVanillaItemByItem(Item $item): PmItem
    {
        foreach (self::$items as $itemName => $replace) {
            if ($item instanceof $replace) {
                return StringToItemParser::getInstance()->parse($itemName) ?? VanillaItems::AIR();
            }
        }
        return VanillaItems::AIR();
    }

    public static function getItem(PmItem $item): Item
    {
        return self::$items[Util::reprocess($item->getVanillaName())] ?? new Item();
    }
}