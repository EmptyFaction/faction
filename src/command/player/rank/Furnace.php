<?php

namespace Faction\command\player\rank;

use CortexPE\Commando\args\OptionArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\crafting\FurnaceRecipe;
use pocketmine\crafting\FurnaceType;
use pocketmine\item\Item;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Furnace extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "furnace",
            "Permet de cuire vos items dans votre inventaire"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $opt = $args["opt"] ?? "hand";

            if ($opt === "all") {
                if (!Rank::hasRank($sender, "vip-plus")) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez pas le grade necessaire pour utiliser cette commande");
                    return;
                }

                $items = $sender->getInventory()->getContents();

                foreach ($items as $item) {
                    $result = $this->smelt($item);

                    if ($result === null) {
                        continue;
                    }

                    $sender->getInventory()->removeItem($item);
                    Util::addItem($sender, $result);
                }

                $sender->sendMessage(Util::PREFIX . "Vous avez cuit tous les items de votre inventaire");
            } else if ($opt === "hand") {
                if (!Rank::hasRank($sender, "vip")) {
                    $sender->sendMessage(Util::PREFIX . "Vous n'avez pas le grade necessaire pour utiliser cette commande");
                    return;
                }

                $item = $sender->getInventory()->getItemInHand();

                if ($item->isNull()) {
                    $sender->sendMessage(Util::PREFIX . "Vous devez avoir un item en main");
                    return;
                }

                $result = $this->smelt($item);

                if ($result === null) {
                    $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas cuire cet item");
                    return;
                }

                $sender->getInventory()->removeItem($item);
                Util::addItem($sender, $result);

                $sender->sendMessage(Util::PREFIX . "Vous avez cuit l'item dans votre main");
            }
        }
    }

    private function smelt(Item $item): ?Item
    {
        $furnaceType = FurnaceType::FURNACE();
        $smelt = Main::getInstance()->getServer()->getCraftingManager()->getFurnaceRecipeManager($furnaceType)->match($item);

        if (!$smelt instanceof FurnaceRecipe || $item->getCount() < 1) {
            return null;
        }

        $result = $smelt->getResult();
        $count = $result->getCount() * $item->getCount();

        return $result->setCount($count);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new OptionArgument("opt", ["all"], true));
    }
}