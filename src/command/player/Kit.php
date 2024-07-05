<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\Rank;
use Faction\Session;
use Faction\Util;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\item\Armor;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Kit extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "kit",
            "Permet d'accèder au menu des kits"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($session) {
                $kits = self::getKits();

                if (!is_string($data) || !isset($kits[$data])) {
                    return;
                }

                $kit = $kits[$data];

                if (!Rank::hasRank($player, $data) && !in_array($data, $session->data["kits"])) {
                    if (!in_array($data, $session->data["kits"])) {
                        $this->purchaseKit($player, $data);
                    } else {
                        $player->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de prendre ce kit");
                    }
                    return;
                } else if ($session->inCooldown("kit_" . $data) && !$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $format = Util::formatDurationFromSeconds($session->getCooldownData("kit_" . $data)[0] - time(), 1);
                    $player->sendMessage(Util::PREFIX . "Vous ne pourrez re-prendre le kit §c" . ucfirst($data) . " §fque dans: §c" . $format);
                    return;
                }

                $session->setCooldown("kit_" . $data, $kit["cooldown"]);

                foreach ($kit["items"] as $item) {
                    if ($item instanceof Armor) {
                        if ($player->getArmorInventory()->getItem($item->getArmorSlot())->equals(VanillaItems::AIR())) {
                            $player->getArmorInventory()->setItem($item->getArmorSlot(), $item);
                            continue;
                        }
                    }

                    $player->getInventory()->addItem($item);
                }

                $player->sendMessage(Util::PREFIX . "Vous venez de recevoir votre kit §c" . ucfirst($data) . " §f!");
            });
            $form->setTitle("Kit");
            $form->setContent(Util::ARROW . "Quel kit voulez-vous prendre");
            foreach (self::getKits() as $name => $value) {
                $format = ucfirst(strtolower($name));

                if (!Rank::hasRank($sender, $name) && !in_array($name, $session->data["kits"])) {
                    if ($value["purchasable"]) {
                        $format .= "\n§cNon acheté";
                    } else {
                        $format .= "\n§cGrade inférieur";
                    }
                } else if ($session->inCooldown("kit_" . $name) && !$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $format = Util::formatDurationFromSeconds($session->getCooldownData("kit_" . $name)[0] - time(), 1);
                    $format .= " §c(Cooldown)\n" . $format;
                }

                $form->addButton($format, -1, "", $name);
            }
            $sender->sendForm($form);
        }
    }

    private function purchaseKit(Player $player, string $kit): void
    {
        $kits = self::getKits();

        if (!isset($kits[$kit])) {
            return;
        }

        $kit = $kits[$kit];

        $session = Session::get($player);

        $form = new CustomForm(function (Player $player, mixed $data) use ($session, $kit) {
            if (!is_array($data) || !isset($data[1]) || !isset($data[2]) || !is_bool($data[2]) || !$data[2]) {
                return;
            }

            $devise = match ($data[1]) {
                1 => " §fECoins",
                default => "$"
            };

            $money = match ($data[1]) {
                1 => "ecoin",
                default => "money"
            };

            if ($kit["price"][$money] > $session->data[$money]) {
                $player->sendMessage(Util::PREFIX . "Vous ne possedez pas assez de " . $devise . " pour acheter le kit §c" . ucfirst($kit["name"]));
                return;
            }

            $session->data["kits"][] = $kit;

            $session->addValue($money, $kit["price"][$money], true);
            $player->sendMessage(Util::PREFIX . "Vous venez d'acheter le kit §c" . ucfirst($kit["name"]) . " §favec §c" . $kit["price"][$money] . $devise);
        });
        $form->setTitle("Kit");

        if ($kit["purchasable"]) {
            $form->addLabel(Util::ARROW . "Voulez vous acheter le kit §c" . ucfirst($kit["name"]) . " §f?\nPour une preview du kit faire §c/previewkit\n\n§fPrix: §c" . Util::formatNumberWithSuffix($kit["price"]["money"]) . "$ §fou §c" . $kit["price"]["ecoin"] . " §fECoins\n\nVous possedez §c" . $session->data["ecoin"] . " §fECoins\nVous possedez §c" . $session->data["money"] . "$\n");
            $form->addDropdown("Méthode de payement", ["Argent", "ECoins"]);
            $form->addToggle("Acheter le kit §c" . ucfirst($kit["name"]) . " §f?", true);
        } else {
            $form->addLabel(Util::ARROW . "Ce kit n'est pas achetable");
        }

        $player->sendForm($form);
    }

    public static function getKits(): array
    {
        $kits = [];

        foreach (Cache::$config["kits"] as $name => $data) {
            $items = [];

            foreach ($data["items"] as $item) {
                $items[] = Util::parseItem($item);
            }

            $kits[$name] = [
                "items" => $items,
                "purchasable" => $data["price"]["ecoin"] > 0,
                "price" => $data["price"],
                "cooldown" => $data["cooldown"],
                "name" => $name
            ];
        }

        return $kits;
    }

    protected function prepare(): void
    {
    }
}