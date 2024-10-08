<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Shop extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "shop",
            "Le marché pour vendre ou acheter des items"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (Session::get($sender)->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $this->categoryForm($sender);
        }
    }

    private function categoryForm(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $this->listMoneyForm($player, $data);
        });

        foreach (Cache::$config["shop"] as $item => $value) {
            $form->addButton($item, -1, "", $item);
        }

        $form->setTitle("Boutique");
        $form->setContent(Util::ARROW . "Cliquez sur le boutton de votre choix");
        $player->sendForm($form);
    }

    private function listMoneyForm(Player $player, string $category): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $this->itemMenuForm($player, $data);
        });

        $form->setTitle("Boutique");
        $form->setContent(Util::ARROW . "Cliquez sur le boutton de votre choix");

        $category = Cache::$config["shop"][$category];
        $items = ($category["type"] === "bourse") ? Util::getBourse() : $category["items"];

        foreach ($items as $item) {
            list($name, $itemName, $buy) = explode(":", $item);

            $form->addButton(
                $name . "\nPrix: §c" . $buy . " §8\$§c/u",
                0,
                "textures/render/" . $itemName,
                $item
            );
        }
        $player->sendForm($form);
    }

    private function itemMenuForm(Player $player, string $item): void
    {
        $item = explode(":", $item);
        $customName = $item[5] ?? null;

        list($name, $itemName, $buy, $sell) = $item;

        $testItem = StringToItemParser::getInstance()->parse($itemName) ?? VanillaItems::AIR();
        $limit = (($items = Util::getItemCount($player, $testItem)) > 256) ? $items : 256;

        $form = new CustomForm(function (Player $player, mixed $data) use ($name, $testItem, $sell, $buy, $customName) {
            if (!is_array($data) || !isset($data[2]) || !isset($data[1])) {
                return;
            } else if (1 > ($count = intval($data[2]))) {
                return;
            }

            $session = Session::get($player);

            if ($data[1] === 0) {
                $money = $session->data["money"];
                $item = $testItem->setCount($count);

                if ($buy * $count > $money) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'argent");
                    return;
                } else if (!$player->getInventory()->canAddItem($item)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de place dans votre inventaire");
                    return;
                }

                if (!is_null($customName)) {
                    $item->setCustomName($customName);
                }

                $session->addValue("money", $buy * $count, true);
                Util::addItem($player, $item);

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient d'acheter au shop " . $name . " x" . $count . " pour " . ($sell * $count));
                $player->sendMessage(Util::PREFIX . "Vous venez d'acheter §c" . $count . " §f" . $name . " pour §c" . ($buy * $count) . "$");
            } else {
                if ($count > Util::getItemCount($player, $testItem)) {
                    $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'item dans votre inventaire");
                    return;
                }

                $session->addValue("money", $sell * $count);
                $player->getInventory()->removeItem($testItem->setCount($count));

                if (isset(Cache::$data["bourse"][$name])) {
                    Cache::$data["bourse"][$name] += $count;
                }

                Main::getInstance()->getLogger()->info("Le joueur " . $player->getName() . " vient de vendre au shop " . $name . " x" . $count . " pour " . ($sell * $count));
                $player->sendMessage(Util::PREFIX . "Vous venez de vendre §c" . $count . " §f" . $name . " pour §c" . ($sell * $count) . "$");
            }
        });
        $form->setTitle("Boutique");
        $form->addLabel("Nombre de §c" . $name . " §rdans votre inventaire: §c" . $items . "\n\n§fPrix achat unité: §c" . $buy . "\n§fPrix vente unité: §c" . $sell);
        $form->addDropdown("Voulez vous achetez ou vendre", (intval($sell) == 0) ? ["Acheter"] : ["Acheter", "Vendre"]);
        $form->addSlider("Combien voulez vous en acheter/vendre?", 1, $limit);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}