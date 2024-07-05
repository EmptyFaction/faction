<?php

namespace Faction\entity\ai;

use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\GameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\player\Player;
use Ramsey\Uuid\Nonstandard\Uuid;

abstract class HumanHostileAI extends HostileAI
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::PLAYER;
    }

    public function getOffsetPosition(Vector3 $vector3): Vector3
    {
        return $vector3->add(0, 1.621, 0);
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $item = is_null($this->getHandItem()) ? VanillaItems::AIR() : $this->getHandItem();

        $session = $player->getNetworkSession();
        $uuid = Uuid::uuid4();

        $session->sendDataPacket(PlayerListPacket::add([PlayerListEntry::createAdditionEntry($uuid, $this->id, $this->getName(), SkinAdapterSingleton::get()->toSkinData($this->getSkin()))]));

        $session->sendDataPacket(AddPlayerPacket::create(
            $uuid,
            $this->getName(),
            $this->getId(),
            "",
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw,
            ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet($item)),
            GameMode::SURVIVAL,
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            UpdateAbilitiesPacket::create(new AbilitiesData(CommandPermissions::NORMAL, PlayerPermissions::VISITOR, $this->getId(), [
                new AbilitiesLayer(
                    AbilitiesLayer::LAYER_BASE,
                    array_fill(0, AbilitiesLayer::NUMBER_OF_ABILITIES, false),
                    0.0,
                    0.0
                )
            ])),
            [],
            "",
            DeviceOS::UNKNOWN
        ));

        $this->sendData([$player], [EntityMetadataProperties::NAMETAG => new StringMetadataProperty($this->getNameTag())]);

        $session->sendDataPacket(PlayerListPacket::remove([PlayerListEntry::createRemovalEntry($uuid)]));
        $session->getEntityEventBroadcaster()->onMobArmorChange([$session], $this);
    }

    abstract public function getHandItem(): ?Item;

    public function getName(): string
    {
        return $this->getNameTag();
    }

    abstract public function getSkin(): Skin;
}