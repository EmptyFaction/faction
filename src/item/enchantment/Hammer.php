<?php /* @noinspection PhpDeprecationInspection */

namespace Faction\item\enchantment;

use Faction\listener\EventsListener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\VanillaItems;
use pocketmine\lang\Translatable;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

class Hammer extends Enchantment
{
    public function __construct()
    {
        parent::__construct(
            $this->getName(),
            $this->getRarity(),
            $this->getPrimaryItemFlags(),
            $this->getSecondaryItemFlags(),
            $this->getMaxLevel(),
        );
    }

    public function getFrenchName(): string
    {
        return "Hammer";
    }

    public function getName(): Translatable|string
    {
        return "Hammer";
    }

    public function getRarity(): int
    {
        return Rarity::UNCOMMON;
    }

    public function getPrimaryItemFlags(): int
    {
        return ItemFlags::PICKAXE;
    }

    public function getSecondaryItemFlags(): int
    {
        return ItemFlags::NONE;
    }

    public function getMaxLevel(): int
    {
        return 1;
    }

    public function onBreak(BlockBreakEvent $event, EnchantmentInstance $enchantmentInstance): void
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        $face = EventsListener::$faces[$player->getXuid()] ?? Facing::UP;

        $position = $block->getPosition();
        $world = $position->getWorld();

        $item = VanillaItems::NETHERITE_PICKAXE();

        for ($a = -1; $a <= 1; $a++) {
            for ($b = -1; $b <= 1; $b++) {
                if ($a === 0 && $b == 0) {
                    continue;
                }

                if ($face == Facing::UP || $face == Facing::DOWN) $target = new Vector3($a, 0, $b);
                if ($face == Facing::NORTH || $face == Facing::SOUTH) $target = new Vector3($a, $b, 0);
                if ($face == Facing::EAST || $face == Facing::WEST) $target = new Vector3(0, $a, $b);

                $world->useBreakOn(
                    $position->addVector($target ?? new Vector3(0, 0, 0)),
                    $item, $player, true
                );
            }
        }
    }
}