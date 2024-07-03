<?php /* @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\BaseCommand;
use Faction\block\EnchantingTable;
use Faction\Util;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Enchant extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "enchant",
            "Ouvre un table d'enchantement pour améliorer l'item dans votre main"
        );

        $this->setPermissions([DefaultPermissions::ROOT_OPERATOR]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $force = $args["force"] ?? false;
            EnchantingTable::openEnchantTable($sender, $force);
        }
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new BooleanArgument("force", true));
    }
}