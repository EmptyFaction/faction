<?php /** @noinspection PhpUnused */

namespace Faction\command\player\rank;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Rename extends BaseCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "rename",
            "Renomme les items"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            if (!Rank::hasRank($sender, "ultra")) {
                $sender->sendMessage(Util::PREFIX . "Vous n'avez pas le grade necessaire pour utiliser cette commande");
                return;
            }

            $session = Session::get($sender);

            if ($session->inCooldown("rename")) {
                $format = Util::formatDurationFromSeconds($session->getCooldownData("rename")[0] - time());
                $sender->sendMessage(Util::PREFIX . "Vous ne pourrez ré-utiliser la commande §c/rename §fque dans: §c" . $format);
                return;
            }

            $form = new CustomForm(function (Player $player, mixed $data) use ($session) {
                if (!is_array($data) || !isset($data[0]) || 1 > strlen($data[0])) {
                    return;
                }

                $renameDisable = [
                    VanillaItems::EXPERIENCE_BOTTLE()->getTypeId(),
                    VanillaBlocks::MONSTER_SPAWNER()->asItem()->getTypeId()
                ];

                if ($player->getInventory()->getItemInHand() === VanillaItems::AIR() || in_array($player->getInventory()->getItemInHand()->getTypeId(), $renameDisable)) {
                    $player->sendMessage(Util::PREFIX . "L'item dans votre main ne peut pas être renommer");
                    return;
                }

                $session->setCooldown("rename", 60 * 3);
                $item = $player->getInventory()->getItemInHand()->setCustomName("§r" . $data[0]);

                $player->getInventory()->setItemInHand($item);
                $player->sendMessage(Util::PREFIX . "Vous venez de renommer l'item dans votre main en " . $data[0]);
            });
            $form->setTitle("Rename");
            $form->addInput(Util::PREFIX . "Tapez un nom personnalisé dans le champ ci-dessous, vous pouvez utiliser les couleurs");
            $sender->sendForm($form);
        }
    }

    protected function prepare(): void
    {
    }
}