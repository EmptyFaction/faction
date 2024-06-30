<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\handler\Cache;
use Faction\Main;
use Faction\Session;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Logs extends FactionCommand
{
    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "logs",
            "Récupére les logs de votre faction"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        self::showLogsForm($sender, $faction);
    }

    public static function showLogsForm(Player $player, string $faction): void
    {
        $logs = Cache::$factions[$faction]["logs"] ?? [];
        $content = "";

        foreach ($logs as $key => $value) {
            $content .= "§c" . date("d-m H:i", $key) . "§f: " . $value . "\n";
        }

        $form = new SimpleForm(null);
        $form->setTitle("Logs de faction");
        $form->setContent($content);
        $player->sendForm($form);
    }

    protected function prepare(): void
    {
    }
}