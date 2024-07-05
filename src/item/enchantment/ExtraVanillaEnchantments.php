<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace Faction\item\enchantment;

use Faction\Util;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment as PmEnchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

class ExtraVanillaEnchantments
{
    public const GLOW = -1;
    public const BOOSTED_SHOVEL = 1000;
    public const DOUBLE_PICKAXE = 1001;
    public const HAMMER = 1002;
    public const TOKENATOR = 1003;

    private static array $enchantments = [];

    public function __construct()
    {
        $this->register(self::GLOW, Glow::class);
        $this->register(self::BOOSTED_SHOVEL, BoostedShovel::class);
        $this->register(self::DOUBLE_PICKAXE, DoublePickaxe::class);
        $this->register(self::HAMMER, Hammer::class);
        $this->register(self::TOKENATOR, Tokenator::class);
    }

    private function register(int $id, string $class): void
    {
        $class = new $class();

        if ($class instanceof Enchantment) {
            self::$enchantments[self::reprocess($class->getName())] = $class;
            EnchantmentIdMap::getInstance()->register($id, new $class());
        }
    }

    private static function reprocess(string $str): string
    {
        return strtolower(str_replace(" ", "_", $str));
    }

    public static function getEnchantment(PmEnchantment $enchantment): Enchantment
    {
        if ($enchantment instanceof Enchantment) {
            return self::$enchantments[self::reprocess($enchantment->getName())] ?? new Glow();
        } else {
            return new Glow();
        }
    }

    public static function getEnchantmentByName(string $name): Enchantment
    {
        return self::$enchantments[self::reprocess($name)] ?? new Glow();
    }

    public static function updateLore(Item $item, EnchantmentInstance $enchantmentInstance): Item
    {
        $enchant = $enchantmentInstance->getType();
        $enchantName = $enchant instanceof Enchantment ? $enchant->getFrenchName() : null;

        if (is_string($enchantName)) {
            $lore = $item->getLore();
            $writed = false;

            if (1 > count($lore)) {
                $lore[] = "§r§c ";
            }

            $text = "§r§7" . $enchantName . " " . Util::formatToRomanNumber($enchantmentInstance->getLevel());

            foreach ($lore as $index => $line) {
                if (str_contains($line, $enchantName)) {
                    $lore[$index] = $text;
                    $writed = true;
                }
            }

            if (!$writed) {
                $lore[] = $text;
            }

            $item->setLore($lore);
        }

        return $item;
    }
}