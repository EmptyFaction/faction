<?php

namespace Faction\entity;

use Faction\handler\Cache;
use pocketmine\entity\animation\HurtAnimation;
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

class SpawnerEntity extends Living
{
    protected int $stack;
    protected string $networkTypeId;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        $this->networkTypeId = $nbt->getString("id", static::getNetworkTypeId());
        $this->stack = $nbt->getInt("stack", 1);

        parent::__construct($location, $nbt);
        $this->setNameTagAlwaysVisible();
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(
            Cache::$config["entities"][$this->networkTypeId]["height"],
            Cache::$config["entities"][$this->networkTypeId]["width"]
        );
    }

    public function getName(): string
    {
        return Cache::$config["entities"][$this->networkTypeId]["name"];
    }

    public function getDrops(): array
    {
        // TODO SET DROPS
        return Cache::$config["entities"][$this->networkTypeId]["drops"];
    }

    public function getXpDropAmount(): int
    {
        return floor(Cache::$config["entities"][$this->networkTypeId]["xp"]);
    }

    public function getFrenchName(): string
    {
        return Cache::$config["entities"][$this->networkTypeId]["french"];
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString("id", $this->networkTypeId);
        $nbt->setInt("stack", $this->stack);
        return $nbt;
    }

    public function attack(EntityDamageEvent $source): void
    {
        $this->updateNametag();

        if ($source->isCancelled()) {
            return;
        }

        if ($source instanceof EntityDamageByEntityEvent) {
            $source->setKnockBack(0);
        }

        $this->broadcastAnimation(new HurtAnimation($this));

        if (
            $source->getFinalDamage() >= $this->getHealth() &&
            $this->getStackSize() > 1
        ) {
            $source->cancel();
            $this->onDeath();
        }

        parent::attack($source);
    }

    public function kill(): void
    {
        if ($this->getStackSize() > 1) {
            for ($i = 1; $i <= $this->getStackSize(); $i++) {
                $this->onDeath();
            }
        }
        parent::kill();
    }

    public function onDeath(): void
    {
        if ($this->stack > 1) {
            $this->stack--;
            $this->setHealth($this->getMaxHealth());
        }
        parent::onDeath();
    }

    public function onUpdate(int $currentTick): bool
    {
        if ($this->closed) {
            return false;
        }

        $this->updateNametag();
        return parent::onUpdate($currentTick);
    }

    public function startDeathAnimation(): void
    {
        if (!$this->isAlive()) {
            parent::startDeathAnimation();
        }
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

    public function updateNametag(): void
    {
        $this->setNameTag("§c".$this->getFrenchName()."s §7[x§c".$this->getStackSize()."§7]\n§7" . round($this->getHealth(), 2) . " §c❤");
    }

    public function getCustomNetworkTypeId(): string
    {
        return $this->networkTypeId;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::AGENT;
    }

    public function getStackSize(): int
    {
        return $this->stack;
    }

    public function setStackSize(int $stack): void
    {
        $this->stack = max(1, $stack);
    }

    public function addStackSize(int $stack): void
    {
        $this->setStackSize($this->stack + $stack);
    }
}