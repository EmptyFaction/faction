<?php

namespace Faction\block;

use Faction\Util;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\item\Sword;
use pocketmine\item\TieredTool;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class EnchantingTable extends Durability
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        if (!$player->isSneaking() && $event->getAction() === $event::RIGHT_CLICK_BLOCK) {
            Util::removeCurrentWindow($player);
            self::openEnchantTable($player, false);

            $event->cancel();
            return true;
        }

        return parent::onInteract($event);
    }

    public static function openEnchantTable(Player $player, bool $force): void
    {
        $item = $player->getInventory()->getItemInHand();

        if (!$item instanceof Armor && !$item instanceof TieredTool) {
            $player->sendMessage(Util::PREFIX . "L'item dans votre main n'est pas enchantable");
            return;
        }

        $form = new SimpleForm(function (Player $player, mixed $data) use ($force) {
            if (!is_string($data)) {
                return;
            }

            self::openEnchantLevelsMenu($player, $data, $force);
        });

        $form->setTitle("Enchantement");
        $form->setContent(Util::ARROW . "Cliquez sur le boutton de votre choix");

        // Id Enchant ; Name Enchant ; Levels Enchant ; XP Enchants ; Lapis Enchants

        if ($item instanceof Sword) {
            $form->addButton("Tranchant", label: EnchantmentIds::SHARPNESS . ";Tranchant;5;6;32");
        } else if ($item instanceof Armor) {
            $form->addButton("Protection", label: EnchantmentIds::PROTECTION . ";Protection;4;8;32");
        } else if ($item instanceof Pickaxe || $item instanceof Axe || $item instanceof Shovel) {
            $form->addButton("Efficacité", label: EnchantmentIds::EFFICIENCY . ";Efficacité;5;6;32");
            $form->addButton("Toucher de soie", label: EnchantmentIds::SILK_TOUCH . ";Toucher de soie;1;60;128");
        }

        $form->addButton("Solidité", label: EnchantmentIds::UNBREAKING . ";Solidité;3;10;32");

        $player->sendForm($form);
    }

    public static function openEnchantLevelsMenu(Player $player, string $data, bool $force): void
    {
        [$enchantId, $enchantName, $maxLevel, $levels, $lapis] = explode(";", $data);
        $x = 1;

        $form = new SimpleForm(function (Player $player, mixed $data) use ($enchantId, $levels, $lapis, $force) {
            if (!is_int($data)) {
                return;
            }

            self::confirmationForm($player, $enchantId, $data + 1, intval($levels), intval($lapis), $force);
        });

        $form->setTitle("Enchantement");
        $form->setContent(Util::ARROW . "Cliquez sur le boutton de votre choix");

        while ($x <= $maxLevel) {
            $form->addButton($enchantName . " " . $x . "\n§c" . intval($levels) * $x . " levels §8et §c" . intval($lapis) * $x . " lapis-lazuli");
            $x++;
        }

        $player->sendForm($form);
    }

    private static function confirmationForm(Player $player, int $enchantId, int $enchantLevel, int $priceLevels, int $priceLapis, bool $force): void
    {
        if ($force) {
            self::enchantItem($player, $enchantId, $enchantLevel, 0, 0);
            return;
        }

        $form = new SimpleForm(function (Player $player, mixed $data) use ($enchantId, $enchantLevel, $priceLevels, $priceLapis) {
            if (!is_string($data) || $data != "yes") {
                return;
            }

            $finalPriceLevels = $enchantLevel * $priceLevels;
            $finalPriceLapis = $enchantLevel * $priceLapis;

            if ($finalPriceLevels > $player->getXpManager()->getXpLevel()) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de niveaux pour enchanter votre item");
                return;
            } else if ($finalPriceLapis > Util::getItemCount($player, VanillaItems::LAPIS_LAZULI())) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de lapis-lazuli pour enchanter votre item");
                return;
            }

            self::enchantItem($player, $enchantId, $enchantLevel, $finalPriceLevels, $finalPriceLapis);
        });
        $form->setTitle("Enchantement");
        $form->setContent(Util::ARROW . "Êtes-vous sur d'enchanter l'item dans votre main ?\n\nLe prix sera de §c" . $enchantLevel * $priceLevels . " §fniveaux ainsi que §c" . $enchantLevel * $priceLapis . " §flapis-lazuli !");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    private static function enchantItem(Player $player, int $enchantId, int $enchantLevel, int $priceLevels, int $priceLapis): void
    {
        $item = $player->getInventory()->getItemInHand();

        if (!$item instanceof Armor && !$item instanceof TieredTool) {
            $player->sendMessage(Util::PREFIX . "L'item dans votre main n'est pas enchantable");
            return;
        }

        $enchant = EnchantmentIdMap::getInstance()->fromId($enchantId);
        $enchantInstance = new EnchantmentInstance($enchant, $enchantLevel);

        if ($item->hasEnchantment($enchant, $enchantLevel)) {
            $player->sendMessage(Util::PREFIX . "Votre item possède déjà cet enchantement");
            return;
        }

        $item->addEnchantment($enchantInstance);

        $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - $priceLevels);
        $player->getInventory()->removeItem(VanillaItems::LAPIS_LAZULI()->setCount($priceLapis));

        $player->getInventory()->setItemInHand($item);
        $player->sendMessage(Util::PREFIX . "L'item dans votre main a été enchanté");
    }

    public function getDurability(): int
    {
        return 20;
    }
}