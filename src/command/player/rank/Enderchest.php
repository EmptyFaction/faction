<?php /** @noinspection PhpUnused */

namespace Faction\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\world\sound\EnderChestOpenSound;

class Enderchest extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "enderchest",
            "Ouvre un enderchest n'importe où"
        );

        $this->setAliases(["ec"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if (!Rank::hasRank($sender, "vip")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas le grade necessaire pour utiliser cette commande");
                return;
            } else if ($session->inCooldown("combat")) {
                $sender->sendMessage(Util::PREFIX . "Cette commande est interdite en combat");
                return;
            } else if ($sender->isCreative()) {
                $sender->sendMessage(Util::PREFIX . "Vous ne pouvez pas executer cette commande en mode spectateur ou créatif");
                return;
            }

            $inventory = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
            $inventory->setName("Coffre de l'Ender");

            $inventory->setListener(function (InvMenuTransaction $transaction) use ($sender): InvMenuTransactionResult {
                $nbt = ($transaction->getOut()->getNamedTag() ?? new CompoundTag());

                if ($nbt->getTag("EnderChestSlots") && $nbt->getInt("EnderChestSlots") === 0) {
                    return $transaction->discard();
                }

                $sender->getEnderInventory()->setItem($transaction->getAction()->getSlot(), $transaction->getIn());
                return $transaction->continue();
            });

            self::setEnderchestGlass($sender, $inventory->getInventory());

            $inventory->getInventory()->setContents($sender->getEnderInventory()->getContents());
            $inventory->send($sender);

            $sender->broadcastSound(new EnderChestOpenSound());
        }
    }

    public static function setEnderchestGlass(Player $player, Inventory $inventory): void
    {
        $rank = Rank::getEqualRankByName($player->getName());
        $slots = Rank::getRankValue($rank, "enderchest");

        $enderchest = $player->getEnderInventory();

        for ($i = 1; $i <= 26; $i++) {
            $item = $player->getEnderInventory()->getItem($i);
            $nbt = ($item->getNamedTag() ?? new CompoundTag());

            if ($nbt->getTag("EnderChestSlots") && $nbt->getInt("EnderChestSlots") === 0) {
                $inventory->setItem($i, VanillaItems::AIR());
            }

            if ($slots <= $i) {
                $glass = VanillaItems::RECORD_CHIRP();
                $glass->setCustomName(" ");

                $nbt = ($glass->getNamedTag() ?? new CompoundTag());
                $nbt->setInt("EnderChestSlots", 0);
                $glass->setNamedTag($nbt);

                $enderchest->setItem($i, $glass);
                $slots++;
            }
        }
    }

    protected function prepare(): void
    {
    }
}