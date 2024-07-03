<?php

namespace Faction\block;

use Faction\item\BoostedShovel;
use Faction\item\Durable as CustomDurable;
use Faction\item\ExtraVanillaItems;
use Faction\item\FarmAxe;
use Faction\item\Fork;
use Faction\item\Sword;
use Faction\item\UnclaimFinder;
use Faction\item\WateringCan;
use Faction\Util;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaArmorMaterials;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\sound\AnvilUseSound;

class Anvil extends Durability
{
    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        if (!$player->isSneaking() && $event->getAction() === $event::RIGHT_CLICK_BLOCK) {
            Util::removeCurrentWindow($player);

            $this->openAnvil($player);
            $event->cancel();

            return true;
        }

        return parent::onInteract($event);
    }

    private function openAnvil(Player $player): void
    {
        $item = $player->getInventory()->getItemInHand();

        if (!$item instanceof Durable) {
            $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être réparé");
            return;
        }

        $price = self::getRepairPrice($item);

        $form = new SimpleForm(function (Player $player, mixed $data) {
            $item = $player->getInventory()->getItemInHand();

            if (!is_string($data) || $data != "yes") {
                return;
            } else if (!$item instanceof Durable) {
                $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être réparé");
                return;
            }

            $price = self::getRepairPrice($item);

            if ($price > $player->getXpManager()->getXpLevel()) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de niveaux pour réparer votre item");
                return;
            } else if ($price > Util::getItemCount($player, VanillaItems::LAPIS_LAZULI())) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez de lapis-lazuli pour réparer votre item");
                return;
            }

            $player->getXpManager()->setXpLevel($player->getXpManager()->getXpLevel() - $price);
            $player->getInventory()->removeItem(VanillaItems::LAPIS_LAZULI()->setCount($price));

            $item->setDamage(0);

            if (!is_null($item->getNamedTag()->getTag(CustomDurable::DAMAGE_TAG))) {
                $item->getNamedTag()->removeTag(CustomDurable::DAMAGE_TAG);
            }

            $player->getInventory()->setItemInHand($item);
            $player->sendMessage(Util::PREFIX . "Vous venez de réparer l'item dans votre main");

            $player->broadcastSound(new AnvilUseSound());
        });
        $form->setTitle("Enclume");
        $form->setContent(Util::ARROW . "Voulez vous réparer l'item dans votre main ?\n\nLe prix sera de §c" . $price . " §fniveaux ainsi que §c" . $price . " §flapis-lazuli !");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    public static function getRepairPrice(Item $item): int
    {
        $count = $item->getCount();
        $extraItem = ExtraVanillaItems::getItem($item);

        if ($item instanceof Armor) {
            return match ($item->getMaterial()) {
                    VanillaArmorMaterials::NETHERITE() => 30,
                    VanillaArmorMaterials::GOLD() => 20, // EMERALD
                    default => 10
                } * $count;
        } else if ($extraItem instanceof FarmAxe || $extraItem instanceof UnclaimFinder || $extraItem instanceof Fork) {
            return 30 * $count;
        } else if ($extraItem instanceof BoostedShovel || $extraItem instanceof WateringCan) {
            return 20 * $count;
        } else if ($extraItem instanceof Sword) {
            return match ($item->getTypeId()) {
                    VanillaItems::GOLDEN_SWORD()->getTypeId() => 20,
                    default => 30
                } * $count;
        } else if ($item instanceof TieredTool && $item->getTier() === ToolTier::NETHERITE()) {
            return 30 * $count;
        }

        return 10 * $count;
    }

    public function getDurability(): int
    {
        return 30;
    }
}