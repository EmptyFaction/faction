<?php /** @noinspection PhpUnused */

namespace Faction\command\staff;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\discord\Discord;
use Faction\handler\discord\EmbedBuilder;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use jojoe77777\FormAPI\SimpleForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class LastInventory extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "lastinventory",
            "Récupére les derniers inventaires d'un joueur avant sa mort"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public static function saveOnlineInventory(Player $player, ?Player $damager, int $killstreak): void
    {
        self::saveInventory($player->getName(), $damager, $player->saveNBT(), $player->getXpManager()->getCurrentTotalXp(), $killstreak);
    }

    public static function saveInventory(string $playerName, ?Player $damager, CompoundTag $nbt, int $xp, int $killstreak): void
    {
        $file = Util::getFile("data/inventories/" . strtolower($playerName));
        $data = $file->getAll();

        $contents = Util::serializeCompoundTag($nbt);

        do {
            $id = rand(1, 9999);
        } while (isset($data["save"][$id]));

        $damagerName = match (true) {
            $damager instanceof Player => $damager->getName(),
            default => "Nature"
        };

        $data["save"][$id] = [
            "data" => $contents,
            "xp" => $xp,
            "date" => date("Y-m-d H:i"),
            "killstreak" => $killstreak,
            "killer" => $damagerName,
        ];

        $file->setAll($data);
        $file->save();
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $target = strtolower($args["joueur"]);

            if (Session::get($sender)->data["rank"] === "staff") {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas la permission de faire cela");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($target) {
                if (!is_string($data) || $data === "none") {
                    return;
                }

                $this->informationForm($player, $target, $data);
            });

            $file = Util::getFile("data/inventories/" . $target);
            $arr = $file->getAll()["save"] ?? [];

            if (count($arr) !== 0) {
                foreach ($arr as $key => $value) {
                    $form->addButton($value["date"], -1, "", $key);
                }
            } else {
                $form->addButton("Aucun inventaire", -1, "", "none");
            }

            $form->setTitle("Inventaires");
            $form->setContent(Util::ARROW . "Cliquez sur le boutton de votre choix");
            $sender->sendForm($form);
        }
    }

    private function informationForm(Player $player, string $target, string $data): void
    {
        $file = Util::getFile("data/inventories/" . $target);
        $information = $file->getAll()["save"][$data] ?? null;

        if (is_null($information)) {
            $player->sendMessage(Util::PREFIX . "Une erreur est survenue, l'inventaire choisi n'existe plus");
            return;
        }

        $message = "§c- §fXP: §c" . $information["xp"] . "\n§c- §fDate: §c" . $information["date"] . "\n§c- §fKillstreak: §c" . $information["killstreak"];

        $form = new SimpleForm(function (?Player $player, mixed $choice) use ($target, $data) {
            if ($choice === 0) {
                $this->sendInventory($player, $target, $data);
            }
        });

        if (!is_null($information["killer"])) {
            $killer = $information["killer"];
            $message .= "\n§c- §fTueur: " . $killer . "\n";

            if (isset(Cache::$bans[strtolower($killer)])) {
                $message .= "§c- §fLe tueur est actuellement banni";
            } else {
                $message .= "§c- §fLe tueur n'est pas banni";
            }
        } else {
            $message .= "§c- Le joueur est mort de façon naturel";
        }

        $form->setTitle("Inventaires");
        $form->setContent(Util::ARROW . "Information sur la mort du joueur " . $target . "\n\n" . $message);
        $form->addButton("Passer à l'inventaire");
        $form->addButton("Annuler");
        $player->sendForm($form);
    }

    private function sendInventory(Player $player, string $target, string $data): void
    {
        $file = Util::getFile("data/inventories/" . $target);
        $inventory = $file->getAll()["save"][$data] ?? null;

        if (is_null($inventory)) {
            Util::removeCurrentWindow($player);
            $player->sendMessage(Util::PREFIX . "L'inventaire vient d'être rendu");
            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName($inventory["date"] . " | " . $target);

        $menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($player, $target, $file, $data): void {
            $array = $file->getAll();
            $inventory = $array["save"][$data] ?? null;

            if ($transaction->getItemClicked()->getCustomName() !== "§r§cRendre l'inventaire") {
                return;
            } else if (is_null($inventory)) {
                Util::removeCurrentWindow($player);
                $player->sendMessage(Util::PREFIX . "L'inventaire vient d'être rendu");
                return;
            }

            $array["reclaim"][$data] = $inventory;
            unset($array["save"][$data]);

            $file->setAll($array);
            $file->save();

            Util::removeCurrentWindow($player);

            $player->sendMessage(Util::PREFIX . "Vous venez de rendre l'inventaire du joueur §c" . $target . " §fde sa mort datant du §c" . $inventory["date"]);
            Main::getInstance()->getLogger()->info("Le staff " . $player->getName() . " vient de rembourser l'inventaire d'une precedente mort du joueur " . $target);

            $embed = new EmbedBuilder();
            $embed->setDescription("**Remboursement**\n\n**Joueur**\n" . $target . "\n\n*Remboursement par le staff: " . $player->getName() . "*");
            $embed->setColor(5636095);
            Discord::send($embed, Cache::$config["sanction-webhook"]);
        }));

        $contents = $inventory["data"];
        $nbt = Util::deserializePlayerData($target, $contents);

        $count = 46;

        foreach (Util::readInventory($nbt) as $slot => $item) {
            $menu->getInventory()->setItem($slot, $item);
        }

        foreach (Util::readArmorInventory($nbt) as $item) {
            $menu->getInventory()->setItem($count, $item);
            $count++;
        }

        $menu->getInventory()->setItem(51, VanillaItems::PAPER()->setCustomName("§r§cRendre l'inventaire"));
        $menu->send($player);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
        $this->registerArgument(0, new RawStringArgument("joueur"));
    }
}