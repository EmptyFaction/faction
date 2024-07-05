<?php

namespace Faction\entity\animation;

use Faction\handler\Box as Api;
use Faction\Main;
use pocketmine\block\Block;
use pocketmine\entity\Attribute;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Box extends Living
{
    private string $networkTypeId;
    private Position $pos;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        $data = explode(":", $nbt->getString("pos", "0:100:0"));

        if (2 > count($data)) {
            $data = [0, 0, 0];
        }

        $this->networkTypeId = $nbt->getString("id", EntityIds::AGENT);
        $this->pos = new Position(intval($data[0]), intval($data[1]), intval($data[2]), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());

        parent::__construct($location, $nbt);
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::AGENT;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString("id", $this->networkTypeId);
        $nbt->setString("pos", $this->pos->getFloorX() . ":" . $this->getPosition()->getFloorY() . ":" . $this->getPosition()->getFloorZ());
        return $nbt;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $source->cancel();

        if ($source instanceof EntityDamageByEntityEvent) {
            $player = $source->getDamager();
            $block = $this->getBlock();

            if (!$player instanceof Player) {
                return;
            }

            if (Api::isBox($block)) {
                if ($player->isSneaking()) {
                    Api::openPreviewBox($player, $block);
                } else {
                    Api::openBox($player, $block);
                }
            }
        }
    }

    private function getBlock(): Block
    {
        return $this->getPosition()->getWorld()->getBlock($this->pos);
    }

    public function getName(): string
    {
        return "Box";
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->setNameTagAlwaysVisible(false);
        $this->setScale(1.8);
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            $this->networkTypeId,
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw,
            $this->location->yaw,
            array_map(function (Attribute $attr): NetworkAttribute {
                return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue(), []);
            }, $this->attributeMap->getAll()),
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            []
        ));
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.6, 0.6);
    }
}