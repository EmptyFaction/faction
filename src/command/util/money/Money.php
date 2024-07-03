<?php /** @noinspection PhpUnused */

namespace Faction\command\util\money;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ExperienceBottle;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Money extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "money",
            "Acceder à son montant de monnaie"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!isset($args["joueur"])) {
            if ($sender instanceof Player) {
                $sender->sendMessage(Util::PREFIX . "Vous possèdez §c" . Session::get($sender)->data["money"] . "$");
            }
        } else {
            /** @noinspection PhpDeprecationInspection */
            $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

            if (!$target instanceof Player) {
                if ($sender instanceof Player) {
                    $sender->sendMessage(Util::PREFIX . "Vous possèdez §c" . Session::get($sender)->data["money"] . "$");
                }
                return;
            }
            $sender->sendMessage(Util::PREFIX . "Le joueur §c" . $target->getName() . "§f possède §c" . Session::get($target)->data["money"] . "$");
        }
    }

    public static function createPaperMoney(int $amount): Item
    {
        $item = VanillaItems::PAPER();
        $item->getNamedTag()->setInt("money", $amount);
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FORTUNE()));
        $item->setCustomName("§r§fBillet de §c" . $amount . "$");
        return $item;
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(true, "joueur"));
    }
}