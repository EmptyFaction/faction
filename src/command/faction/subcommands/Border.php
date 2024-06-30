<?php

namespace Faction\command\faction\subcommands;

use Faction\command\faction\FactionCommand;
use Faction\Main;
use Faction\Session;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;

class Border extends FactionCommand
{
    protected bool $requiresFaction = false;

    public function __construct()
    {
        parent::__construct(
            Main::getInstance(),
            "border",
            "Active la bordure des chunks"
        );

        $this->setPermissions([DefaultPermissions::ROOT_USER]);
    }

    public function onNormalRun(Player $sender, Session $session, ?string $faction, array $args): void
    {
        Session::get($sender)->removeCooldown("cmd");
        $sender->chat("/border");
    }
}