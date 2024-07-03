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

        $this->setAliases(["job", "metier", "metiers"]);
        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $form = new SimpleForm(function (Player $player, mixed $data) {
                if (!is_string($data) || !isset(Cache::$config["jobs"][$data])) {
                    return;
                }

                $this->jobInformation($player, $data);
            });
            $form->setTitle("Métiers");
            $form->setContent(Util::PREFIX . "Cliquez sur un métier pour avoir plus d'informations sur son propos");
            foreach (Cache::$config["jobs"] as $name => $data) {
                $form->addButton("§8" . $name . "§c: §8" . Api::getProgressBar($sender, $name, "UI") . "\n" . Api::getProgressBar($sender, $name), -1, "", $name);
            }
            $sender->sendForm($form);
        }
    }

    private function jobInformation(Player $player, string $job): void
    {
        $form = new SimpleForm(null);
        $form->setTitle("Métiers");

        $label = Util::PREFIX . "§cMétier de " . ucfirst($job) . "\n\n";

        switch ($job) {
            case "Miner":
                $label .= "§fPierre: §c1xp\n§fPierre taillée: §c1xp\n§fMinerai de charbon: §c2xp\n§fMinerai de fer: §c4xp\n§fMinerai d'or: §c10xp\n§fMinerai de diamant: §c20xp\n§fMinerai d'émeraude: §c40xp\n§fMinerai de rubis: §c80xp\n§fMinerai de lapis: §c5xp\n§fMinerai de redstone: §c2xp";
                break;
            case "Farmer":
                $label .= "§fBlé: §c1-3xp\n§fCarrote: §c1-3xp\n§fBetterave: §c1-3xp\n§fPatate: §c1-3xp\n§fMelon: §c1-3xp\n§fBambou: §c1xp";
                break;
            case "Hunter":
                $label .= "§fKill: §c50xp\n§fZombie: §c1-6xp\n§fWither Squelette: §c1-6xp\n§fEnderman: §c1-6xp\n§fCreeper: §c1-6xp\n§fPiglin: §c1-6xp";
                break;
        }

        $label .= "\n\n" . Util::ARROW . "§cRécomponses:\n\n";

        foreach (Cache::$config["jobs"][$job] as $level => $data) {
            $label .= "\n§cNiveau " . $level . " §f: " . ucfirst($data["reward"]["name"]);
        }

        $form->setContent($label);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}