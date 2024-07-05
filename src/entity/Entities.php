<?php

namespace Faction\entity;

use Closure;
use Faction\entity\animation\BoxItem;
use Faction\entity\animation\DefaultFloatingText;
use Faction\entity\animation\DynamicFloatingText;
use Faction\entity\animation\Message;
use Faction\entity\effect\Effects;
use Faction\entity\animation\Box;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\World;
use ReflectionClass;

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

        EntityFactory::getInstance()->register(BoxItem::class, function (World $world, CompoundTag $nbt): BoxItem {
            $itemTag = $nbt->getCompoundTag(ItemEntity::TAG_ITEM);
            $item = Item::nbtDeserialize($itemTag);

            if ($itemTag === null) throw new SavedDataLoadingException("Expected \"" . ItemEntity::TAG_ITEM . "\" NBT tag not found");
            if ($item->isNull()) throw new SavedDataLoadingException("Item is invalid");

            return new BoxItem(EntityDataHelper::parseLocation($nbt, $world), $item, $nbt);
        }, ["Box"]);

        $this->registerEntity(Box::class, "nitro:box_vote");
        $this->registerEntity(Box::class, "nitro:box_epic");
        $this->registerEntity(Box::class, "nitro:box_basic");
        $this->registerEntity(Box::class, "nitro:box_irl");
        $this->registerEntity(Box::class, "nitro:box_event");

        new Effects();
    }

    public function registerEntity(string $className, string $identifier, ?Closure $creationFunc = null, string $behaviourId = ""): void
    {
        EntityFactory::getInstance()->register($className, $creationFunc ?? static function (World $world, CompoundTag $nbt) use ($className): Entity {
            return new $className(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, [$identifier]);

        $this->updateStaticPacketCache($identifier, $behaviourId);
    }

    private function updateStaticPacketCache(string $identifier, string $behaviourId): void
    {
        $instance = StaticPacketCache::getInstance();
        $property = (new ReflectionClass($instance))->getProperty("availableActorIdentifiers");

        /** @var AvailableActorIdentifiersPacket $packet */
        $packet = $property->getValue($instance);

        /** @var CompoundTag $root */
        $root = $packet->identifiers->getRoot();

        ($root->getListTag("idlist") ?? new ListTag())->push(CompoundTag::create()
            ->setString("id", $identifier)
            ->setString("bid", $behaviourId));

        $packet->identifiers = new CacheableNbt($root);
    }
}