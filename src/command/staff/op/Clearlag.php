<?php /** @noinspection PhpUnused */

namespace Faction\command\staff\op;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\task\repeat\PlayerTask;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\plugin\PluginBase;

class Clearlag extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "forceclearlag",
            "Effectue un clearlagg forcé"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        self::clearlag(true);
    }

    public static function clearlag(bool $force = false): void
    {
        $count = 0;

        foreach (Main::getInstance()->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof ItemEntity || $entity instanceof ExperienceOrb) {
                    if ($entity instanceof ItemEntity) {
                        $count++;
                    }

                    $entity->flagForDespawn();
                }
            }
        }

        PlayerTask::resetClearlag();

        $word = $force ? "forcé" : "automatique";
        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $count . " §fentitée(s) ont été supprimée(s) lors d'un nettoyage " . $word);
    }

    protected function prepare(): void
    {
    }
}