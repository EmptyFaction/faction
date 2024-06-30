<?php /** @noinspection PhpUnused */

namespace Faction\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\math\AxisAlignedBB;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Near extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "near",
            "Voir la liste des joueurs aux alentours"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!Rank::hasRank($sender, "vip")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas le grade necessaire pour utiliser cette commande");
                return;
            }

            $session = Session::get($sender);

            if ($session->inCooldown("near")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("near")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §c/near §fque dans: §c" . $format);
                return;
            }

            $near = $this->getNearbyPlayers($sender, 125);
            $players = count($near) > 0 ? implode("§c, ", $near) : "§cAucun joueur trouvé";

            $sender->sendMessage(Util::PREFIX . "Voici la liste des joueurs présents dans un rayon de 125 blocs : §c" . $players);
            $session->setCooldown("near", 60 * (30 - (Rank::getRankPos(Rank::getRank($sender)) * 5)));
        }
    }

    private function getNearbyPlayers(Player $player, int $radius = null): ?array
    {
        $players = [];

        $x = $player->getPosition()->getFloorX();
        $y = $player->getPosition()->getFloorY();
        $z = $player->getPosition()->getFloorZ();

        $entities = $player->getWorld()->getNearbyEntities(new AxisAlignedBB($x - $radius, $y - 250, $z - $radius, $x + $radius, $y + 250, $z + $radius), $player);

        foreach ($entities as $entity) {
            if ($entity instanceof Player) {
                $distance = round($player->getPosition()->distance($entity->getPosition()));
                $players[] = "§f" . $entity->getName() . " §c(§f{$distance}§c)";
            }
        }
        return $players;
    }

    protected function prepare(): void
    {
    }
}