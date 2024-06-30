<?php /** @noinspection PhpUnused */

namespace Faction\command\player;

use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\Jobs as Api;
use Faction\Util;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Jobs extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "jobs",
            "Ouvre le menu des jobs"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $form = new SimpleForm(function (Player $player, mixed $data) {
                if (!is_string($data) || !in_array($data, ["mineur", "farmeur", "hunter"])) {
                    return;
                }

                $this->jobInformation($player, $data);
            });
            $form->setTitle("Métiers");
            $form->addButton("§8Mineur§c: §8" . Api::getProgressBar($sender, "Mineur", "UI") . "\n" . Api::getProgressBar($sender, "Mineur"), -1, "", "mineur");
            $form->addButton("§8Farmeur§c: §8" . Api::getProgressBar($sender, "Farmeur", "UI") . "\n" . Api::getProgressBar($sender, "Farmeur"), -1, "", "farmeur");
            $form->addButton("§8Hunter§c: §8" . Api::getProgressBar($sender, "Hunter", "UI") . "\n" . Api::getProgressBar($sender, "Hunter"), -1, "", "hunter");
            $sender->sendForm($form);
        }
    }

    private function jobInformation(Player $player, string $job): void
    {
        $form = new SimpleForm(null);
        $form->setTitle("Métiers");

        $label = Util::PREFIX . "§cMétier de " . $job . "\n\n";

        switch ($job) {
            case "mineur":
                $label .= "§fPierre: §c1xp\n§fPierre taillé: §c1xp\n§fLuckyBlock: §c5xp\n§fEmeraude: §c15xp";
                break;
            case "farmeur":
                $label .= "§fBlé: §c1-3xp\n§fCarrote: §c1-3xp\n§fBetterave: §c1-3xp\n§fPatate: §c1-3xp\n§fMelon: §c1-3xp\n§fBambou: §c1xp\n\n§fGraines en Iris: §c5xp";
                break;
            case "hunter":
                $label .= "§fKill: §c50xp\n§fPlus votre killstreak (exemple: 50 + 10)";
                break;
        }

        $label .= "\n\n" . Util::ARROW . "§cRécomponses:\n\n";

        for ($i = 2; $i <= 20; $i++) {
            $data = Cache::$config["jobs"]["rewards"][strval($i)];
            $data = explode(":", $data);

            $name = match (intval($data[0])) {
                0 => $data[3]
            };

            $label .= "§fNiveau " . $i . ": §c" . ucfirst(strtolower($name)) . "\n";
        }

        $form->setContent($label);
        $form->addButton("Quitter");
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}