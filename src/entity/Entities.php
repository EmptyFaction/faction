<?php

namespace Faction\entity;

use Faction\entity\effect\Effects;
use Faction\entity\animation\Box;
use Faction\entity\animation\DefaultFloatingText;
use Faction\entity\animation\DynamicFloatingText;
use Faction\entity\animation\Message;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
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

        EntityFactory::getInstance()->register(DynamicFloatingText::class, function (World $world, CompoundTag $nbt): DynamicFloatingText {
            return new DynamicFloatingText(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["DynamicFloatingText"]);

        EntityFactory::getInstance()->register(DefaultFloatingText::class, function (World $world, CompoundTag $nbt): DefaultFloatingText {
            return new DefaultFloatingText(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["DefaultFloatingText"]);

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

        EntityFactory::getInstance()->register(Box::class, function (World $world, CompoundTag $nbt): Box {
            $itemTag = $nbt->getCompoundTag(ItemEntity::TAG_ITEM);
            $item = Item::nbtDeserialize($itemTag);

            if ($itemTag === null) throw new SavedDataLoadingException("Expected \"" . ItemEntity::TAG_ITEM . "\" NBT tag not found");
            if ($item->isNull()) throw new SavedDataLoadingException("Item is invalid");

            return new Box(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
        }, ["Box"]);

        new Effects();
    }
}