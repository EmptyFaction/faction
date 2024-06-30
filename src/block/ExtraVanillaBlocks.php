<?php

namespace Faction\block;

use pocketmine\block\Block as PmBlock;
use pocketmine\block\MonsterSpawner as PmMonsterSpawner;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\CloningRegistryTrait;

/**
 *
 * @method static PmMonsterSpawner MONSTER_SPAWNER()
 *
 */
final class ExtraVanillaBlocks
{
    use CloningRegistryTrait;

    private static array $blocks = [];

    public function __construct()
    {
        self::addBlock(VanillaBlocks::ANVIL(), new Anvil());
        self::addBlock(VanillaBlocks::ENCHANTING_TABLE(), new EnchantingTable());
        self::addBlock(VanillaBlocks::CRYING_OBSIDIAN(), new EmeraldObsidian());
        self::addBlock(VanillaBlocks::ENDER_CHEST(), new Enderchest());
        self::addBlock(VanillaBlocks::IRON_DOOR(), new IronDoor());
        self::addBlock(VanillaBlocks::IRON(), new Iron());
        self::addBlock(VanillaBlocks::IRON_TRAPDOOR(), new IronTrapDoor());
        self::addBlock(VanillaBlocks::OBSIDIAN(), new Obsidian());
        self::addBlock(VanillaBlocks::NETHER_QUARTZ_ORE(), new Luckyblock());
        self::addBlock(ExtraVanillaBlocks::MONSTER_SPAWNER(), new MonsterSpawner());

        new World();
    }

    public static function addBlock(PmBlock $block, Block $replace): void
    {
        self::$blocks[$block->getTypeId()] = $replace;
    }

    public static function getBlock(PmBlock $block): Block
    {
        return self::$blocks[$block->getTypeId()] ?? new Block();
    }

    protected static function setup(): void
    {
        self::_registryRegister("monster_spawner", MonsterSpawner::override());
    }
}