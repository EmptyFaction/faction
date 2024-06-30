<?php

namespace Faction\entity;

use Faction\entity\effect\Effects;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\World;

class Entities
{
    public function __construct()
    {
        EntityFactory::getInstance()->register(LogoutNpc::class, function (World $world, CompoundTag $nbt): LogoutNpc {
            return new LogoutNpc(EntityDataHelper::parseLocation($nbt, $world), LogoutNpc::parseSkinNBT($nbt), $nbt);
        }, ["LogoutEntity"]);

        EntityFactory::getInstance()->register(Nexus::class, function (World $world, CompoundTag $nbt): Nexus {
            return new Nexus(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["NexusEntity"]);

        EntityFactory::getInstance()->register(DefaultFloatingText::class, function (World $world, CompoundTag $nbt): DefaultFloatingText {
            return new DefaultFloatingText(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["FloatingText"]);

        EntityFactory::getInstance()->register(EnderPearl::class, function (World $world, CompoundTag $nbt): EnderPearl {
            return new EnderPearl(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["ThrownEnderpearl", EntityIds::ENDER_PEARL]);

        EntityFactory::getInstance()->register(Message::class, function (World $world, CompoundTag $nbt): Message {
            return new Message(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["MessageEntity"]);

        EntityFactory::getInstance()->register(Creeper::class, function (World $world, CompoundTag $nbt): Creeper {
            return new Creeper(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Creeper", EntityIds::CREEPER]);

        EntityFactory::getInstance()->register(SpawnerEntity::class, function (World $world, CompoundTag $nbt): SpawnerEntity {
            return new SpawnerEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["SpawnerEntity"]);

        new Effects();
    }
}