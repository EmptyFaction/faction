<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class CoinFlip extends BaseCommand
{
    public static array $coinflip = [];

    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "coinflip",
            "Joue au pile ou face avec les joueurs du serveur"
        );

        $this->setAliases(["cf"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $form = new SimpleForm(function (Player $player, mixed $data) {
                if (!is_int($data)) {
                    return;
                }

                switch ($data) {
                    case 0:
                        $this->createCoinFlip($player);
                        return;
                    case 1:
                        $this->showCoinFlip($player);
                        return;
                }
            });
            $form->setTitle("Coinflip");
            $form->setContent(Util::ARROW . "Cliquez sur le boutton de votre choix");
            $form->addButton("Créer");
            $form->addButton("Rejoindre §c(" . count(CoinFlip::$coinflip) . ")");
            $sender->sendForm($form);
        }
    }

    private function createCoinFlip(Player $player): void
    {
        $form = new CustomForm(function (Player $player, mixed $data) {
            if (!is_array($data) || !isset($data[0])) {
                return;
            } else if (!is_numeric($data[0])) {
                $player->sendMessage(Util::PREFIX . "Le prix indiqué est invalide");
                return;
            }

            $session = Session::get($player);
            $price = intval($data[0]);

            if (1000 > $price) {
                $player->sendMessage(Util::PREFIX . "Vous ne pouvez pas miser moins de §c1000$");
                return;
            } else if ($price > $session->data["money"]) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'argent pour créer ce coinflip");
                return;
            } else if ($session->inCooldown("coinflip")) {
                $player->sendMessage(Util::PREFIX . "Vous devez encore attendre §c" . ($session->getCooldownData("coinflip")[0] - time()) . " §fsecondes avant de pouvoir re-créer un coinflip");
                return;
            }

            $session->addValue("money", $price, true);

            while (true) {
                $id = rand(1, 9999);

                if (!isset(CoinFlip::$coinflip[$id])) {
                    break;
                }
            }

            CoinFlip::$coinflip[$id] = [
                "username" => strtolower($player->getName()),
                "price" => $price
            ];

            $session->setCooldown("coinflip", 30);
            $player->sendMessage(Util::PREFIX . "Vous venez de miser §c" . $price . "$ §f!");
        });
        $form->setTitle("Coinflip");
        $form->addInput(Util::ARROW . "Choisissez le prix de votre choix");
        $player->sendForm($form);
    }

    private function showCoinFlip(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if (!is_string($data)) {
                return;
            } else if ($data === "refresh") {
                $this->showCoinFlip($player);
                return;
            }

            $this->confirmationForm($player, $data);
        });
        $form->setTitle("Coinflip");
        $form->setContent(Util::ARROW . "Cliquez sur le boutton de votre choix");

        foreach (CoinFlip::$coinflip as $id => $value) {
            $form->addButton($value["username"] . ": §c" . $value["price"], -1, "", $id);
        }
        $form->addButton("Rafraîchir", -1, "", "refresh");
        $player->sendForm($form);
    }

    private function confirmationForm(Player $player, string $id): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) use ($id) {
            if (!is_string($data) || $data != "yes") {
                return;
            }

            if (!isset(CoinFlip::$coinflip[$id])) {
                if (isset(CoinFlip::$coinflip[$id])) {
                    unset(CoinFlip::$coinflip[$id]);
                }

                $player->sendMessage(Util::PREFIX . "Le coinflip que vous venez de choisir n'existe plus");
                return;
            } else {
                $target = Main::getInstance()->getServer()->getPlayerExact(CoinFlip::$coinflip[$id]["username"]);

                if (!$target instanceof Player || !$target->isOnline()) {
                    $player->sendMessage(Util::PREFIX . "Le coinflip que vous venez de choisir n'existe plus");
                    return;
                }
            }

            $session = Session::get($player);
            $price = CoinFlip::$coinflip[$id]["price"];

            if (CoinFlip::$coinflip[$id]["price"] > $session->data["money"]) {
                $player->sendMessage(Util::PREFIX . "Vous n'avez pas assez d'argent pour rejoindre ce coinflip");
                return;
            }

            $_price = ($price * 2) * 0.95;
            $rand = mt_rand(250, 9999);

            $session->addValue("money", $price, true);

            $players = array_merge(
                array_fill(0, $rand, $player->getName()),
                array_fill(0, $rand, $target->getName())
            );

            shuffle($players);

            if ($players[array_rand($players)] === $player->getName()) {
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $player->getName() . " §fa remporté un coinflip de §c" . Util::formatNumberWithSuffix($price) . "$ §fcontre §c" . $target->getName() . " §f!");
                $session->addValue("money", $_price);
            } else {
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $target->getName() . " §fa remporté un coinflip de §c" . Util::formatNumberWithSuffix($price) . "$ §fcontre §c" . $player->getName() . " §f!");
                Session::get($target)->addValue("money", $_price);
            }

            unset(CoinFlip::$coinflip[$id]);
        });
        $form->setTitle("Coinflip");
        $form->setContent(Util::ARROW . "Êtes vous sur de rejoindre ce coinflip ?");
        $form->addButton("Oui", -1, "", "yes");
        $form->addButton("Non", -1, "", "no");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}