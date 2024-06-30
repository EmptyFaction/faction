<?php /** @noinspection PhpUnused */

namespace Faction\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Util;
use muqsit\invmenu\InvMenu;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Craft extends BaseCommand
{
    public const INV_MENU_TYPE_WORKBENCH = "nitro:workbench";

    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "craft",
            "Ouvre un établi n'importe où"
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

            InvMenu::create(self::INV_MENU_TYPE_WORKBENCH)->send($sender);
        }
    }

    protected function prepare(): void
    {
    }
}