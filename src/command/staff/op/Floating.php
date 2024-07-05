<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Faction\entity\animation\Box;
use Faction\entity\animation\DefaultFloatingText;
use Faction\entity\animation\DynamicFloatingText;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

class Floating extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "floating",
            "Fait disparaitre ou apparaitre les floatings texts"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        switch ($args["opt"]) {
            case "spawn":
                foreach (Cache::$config["floatings"] as $key => $value) {
                    list ($x, $y, $z, $world) = explode(":", $key);

                    $entity = new DynamicFloatingText(new Location(floatval($x), floatval($y), floatval($z), Main::getInstance()->getServer()->getWorldManager()->getWorldByName($world), 0, 0));
                    $entity->spawnToAll();
                }

                foreach (Cache::$config["box"] as $name => $value) {
                    [$x, $y, $z, $yaw] = explode(":", $value["pos"]);

                    $pos = new Position(intval($x), intval($y), intval($z), Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld());

                    $entity = new DefaultFloatingText(
                        Location::fromObject($pos->add(0.5, 1.2, 0.5), $pos->getWorld()),
                        CompoundTag::create()
                            ->setString("floating", $value["floating"])
                            ->setString("type", "box_" . strtolower($name))
                    );

                    $entity->spawnToAll();

                    $entity = new Box(
                        Location::fromObject($pos->add(0.5, 0, 0.5), $pos->getWorld(), intval($yaw)),
                        CompoundTag::create()
                            ->setString("id", "nitro:box_" . $value["entity"])
                            ->setString("pos", $value["pos"])
                    );

                    $entity->spawnToAll();
                }

                $sender->sendMessage(Util::PREFIX . "Vous venez de faire apparaitre les floatings texts");
                break;
            case "despawn":
                foreach (Main::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
                    foreach ($world->getEntities() as $entity) {
                        if ($entity instanceof DynamicFloatingText || $entity instanceof DefaultFloatingText || $entity instanceof Box) {
                            $entity->close();
                        }
                    }
                }

                $sender->sendMessage(Util::PREFIX . "Vous venez de supprimer les floatings texts");
                break;
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["spawn", "despawn"]));
    }
}