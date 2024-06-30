<?php /** @noinspection PhpUnused */

namespace Faction\command\player\rank;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Stuff extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "stuff",
            "Permet de regarder l'inventaire d'un joueur adverse"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            /** @noinspection PhpDeprecationInspection */
            $player = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!Rank::hasRank($sender, "vip-plus")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            } else if (!$player instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Le joueur n'éxiste pas ou n'est pas connecté sur le serveur");
                return;
            }

            $session = Session::get($sender);

            if ($session->inCooldown("stuff")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("stuff")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §c/stuff §fque dans: §c" . $format);
                return;
            }

            $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

            $menu->setListener(InvMenu::readonly());
            $menu->setName("Inventaire de " . $player->getName());

            $contents = $player->getInventory()->getContents(true);

            foreach ($contents as $slot => $item) {
                $menu->getInventory()->setItem($slot, $item);
            }

            $menu->getInventory()->setItem(46, $player->getArmorInventory()->getHelmet());
            $menu->getInventory()->setItem(48, $player->getArmorInventory()->getChestplate());
            $menu->getInventory()->setItem(50, $player->getArmorInventory()->getLeggings());
            $menu->getInventory()->setItem(52, $player->getArmorInventory()->getBoots());
            $menu->send($sender);

            $session->setCooldown("stuff", 60 * (35 - (Rank::getRankPos(Rank::getRank($sender)) * 5)));
            $player->sendMessage(Util::PREFIX . "§c" . $sender->getName() . " §fvérifie votre stuff...");
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(name: "joueur"));
    }
}