<?php

namespace Faction\task;

use Faction\entity\animation\BoxItem as Entity;
use Faction\entity\animation\DefaultFloatingText;
use Faction\handler\Box as Api;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\block\Block;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpCollectSound;

class BoxAnimationTask extends Task
{
    private int $ticks = 200;
    private int $waitCancelTicks = -1;

    private ?Entity $lastEntity = null;

    public function __construct(
        private readonly Player $player,
        private readonly Block  $block
    )
    {
    }

    public function onRun(): void
    {
        $this->ticks--;
        $this->waitCancelTicks--;

        if ($this->waitCancelTicks === 1 || !$this->player->isConnected()) {
            if ($this->lastEntity instanceof Entity) {
                $this->lastEntity->close();
            }

            $this->getHandler()->cancel();

            if ($this->player->isConnected()) {
                $this->updateFloating(true);
            }

            return;
        } else if ($this->waitCancelTicks > 1) {
            return;
        }

        $i = (200 - $this->ticks);
        $freq = min(20, intval(1 + ($i - 1) / 10));

        $position = $this->block->getPosition();
        $item = $this->getItem();

        $data = Api::getBoxData($this->block);

        Session::get($this->player)->setCooldown("box_" . strtolower($data["name"]), 5);

        if ($i % max(1, $freq) == 0) {
            $this->updateFloating(false);
            $position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), new ClickSound(), [$this->player]);

            if ($this->lastEntity instanceof Entity) {
                $this->lastEntity->close();
            }

            $entity = new Entity(Location::fromObject($position->add(0.5, 1, 0.5), $position->getWorld()), $item["item"]);
            $entity->setNameTag($item["name"]);
            $entity->spawnTo($this->player);

            $this->lastEntity = $entity;
        }

        if ($this->ticks === 0) {
            $this->updateFloating(false);
            $this->waitCancelTicks = 90;

            $position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), new BlazeShootSound(), [$this->player]);
            $position->getWorld()->addSound($position->add(0.5, 0.5, 0.5), new XpCollectSound(), [$this->player]);

            $this->lastEntity->close();

            $randomItem = $this->chooseRandomItem();

            $entity = new Entity(Location::fromObject($position->add(0.5, 1, 0.5), $position->getWorld()), $randomItem["item"]);
            $entity->setNameTag(Util::ARROW . $randomItem["name"] . Util::IARROW);
            $entity->spawnTo($this->player);

            $this->lastEntity = $entity;
            $this->player->sendMessage(Util::PREFIX . "Grace à la box §c§l" . strtoupper($data["name"]) . " §r§fvous venez de gagner " . $randomItem["name"]);

            Util::addItem($this->player, $randomItem["item"]);
        }
    }

    public function updateFloating(bool $spawn): void
    {
        $data = Api::getBoxData($this->block);

        list ($x, $y, $z) = explode(":", $data["pos"]);

        $pos = new Vector3(intval($x), intval($y), intval($z));
        $entity = Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getNearestEntity($pos, 2, DefaultFloatingText::class);

        if ($entity instanceof DefaultFloatingText && $this->player->isConnected()) {
            if ($spawn) {
                $entity->spawnTo($this->player);
            } else {
                /** @noinspection PhpDeprecationInspection */
                $entity->despawnFrom($this->player);
            }
        }
    }

    public function getItem(): array
    {
        $data = Api::getBoxData($this->block);
        $items = Api::getItems($data["name"]);

        $randomKey = array_rand($items);
        $randomItem = $items[$randomKey];

        return [
            "name" => Util::ARROW . ucfirst($randomKey) . Util::IARROW,
            "item" => $randomItem
        ];
    }

    public function chooseRandomItem(): array
    {
        $data = Api::getBoxData($this->block);
        $rewards = $data["rewards"];

        $totalWeight = 0;
        $weightedRewards = [];

        foreach ($rewards as $reward) {
            $weight = intval($reward[0]);
            $name = $reward[1];

            $item = Util::parseItem($reward[2]);

            $totalWeight += $weight;

            $weightedRewards[] = [
                "weight" => $weight,
                "name" => $name,
                "item" => $item
            ];
        }

        $randomWeight = mt_rand(0, $totalWeight - 1);
        $currentWeight = 0;

        foreach ($weightedRewards as $reward) {
            $currentWeight += $reward["weight"];

            if ($randomWeight < $currentWeight) {
                return [
                    "name" => $reward["name"],
                    "item" => $reward["item"]
                ];
            }
        }

        return [];
    }
}