<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\Session;
use Faction\Util;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Reclaim extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "reclaim",
            "Recupere ses récompenses journalières ou un remboursement"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);
            $file = Util::getFile("data/inventories/" . strtolower($sender->getName()));

            $inventorys = $file->getAll()["reclaim"] ?? [];

            if (count($inventorys) > 0) {
                $this->sendRefundForm($sender);
            }

            // La faut lui dire d'aller nsm y'a pas de inventaire dispo
        }
    }

    private function sendRefundForm(Player $player): void
    {
        $file = Util::getFile("data/inventories/" . strtolower($player->getName()));
        $inventorys = $file->getAll()["reclaim"] ?? [];

        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            }

            $this->informationForm($player, $data);
        });

        foreach ($inventorys as $key => $value) {
            $form->addButton("Mort par §c" . $value["killer"], -1, "", $key);
        }

        $form->setTitle("Remboursement");
        $form->setContent(Util::ARROW . "Cliquez sur le bouton de choix");
        $player->sendForm($form);
    }

    private function informationForm(Player $player, string $inventory): void
    {
        $file = Util::getFile("data/inventories/" . strtolower($player->getName()));

        $data = $file->getAll()["reclaim"][$inventory] ?? [];

        $nbt = Util::deserializePlayerData($player->getName(), $data["data"] ?? "");
        $items = $this->getItems($nbt);

        $form = new SimpleForm(function (Player $player, mixed $button) use ($file, $inventory) {
            if ($button === 0) {
                $data = $file->getAll()["reclaim"][$inventory] ?? [];
                $nbt = Util::deserializePlayerData($player->getName(), $data["data"] ?? "");

                foreach ($this->getItems($nbt) as $item) {
                    if ($item instanceof Item && !$item->equals(VanillaItems::SPLASH_POTION())) {
                        Util::addItem($player, $item);
                    }
                }

                $session = Session::get($player);
                $session->addValue("death", 1, true);

                $player->sendMessage(Util::PREFIX . "Vous venez de récupérer votre inventaire que vous avez perdu le §c" . $data["date"]);
                $player->sendMessage(Util::PREFIX . "Une mort a été soustraite de votre compteur de mort");
                $player->sendMessage(Util::PREFIX . "Vous venez de récupérer votre xp");

                $player->getXpManager()->setCurrentTotalXp($data["xp"] + $player->getXpManager()->getCurrentTotalXp());

                if ($data["killstreak"] > $session->data["killstreak"]) {
                    $session->data["killstreak"] = $data["killstreak"];
                    $player->sendMessage(Util::PREFIX . "Votre killstreak a été restoré");
                }

                $data = $file->getAll();
                unset($data["reclaim"][$inventory]);

                $file->setAll($data);
                $file->save();
            }
        });

        $form->setTitle("Remboursement");
        $form->setContent("§fL'inventaire contient §c" . count($items) . " §fitems\nVerifiez que votre inventaire a assez de place pour récupérer les items");
        $form->addButton("Récupérer l'inventaire");
        $form->addButton("Récupérer plus tard");
        $player->sendForm($form);
    }

    private function getItems(CompoundTag $nbt): array
    {
        $inventory = Util::readInventory($nbt);
        $armorInventory = Util::readArmorInventory($nbt);

        return array_merge($inventory, $armorInventory);
    }

    protected function prepare(): void
    {
    }
}