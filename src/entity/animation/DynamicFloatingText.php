<?php

namespace Faction\entity\animation;

use Faction\handler\Cache;
use Faction\handler\Faction;
use Faction\task\repeat\KothTask;
use Faction\task\repeat\OutpostTask;
use Faction\Util;

class DynamicFloatingText extends FloatingText
{
    protected function getPeriod(): ?int
    {
        return $this->period;
    }

    protected function getUpdate(): string
    {
        $floatings = Cache::$config["floatings"];

        $position = $this->getLocation();
        $text = $position->getX() . ":" . $position->getY() . ":" . $position->getZ() . ":" . $position->getWorld()->getFolderName();

        $name = $floatings[$text] ?? false;

        if (is_bool($name)) {
            return "";
        }

        switch ($name) {
            case "koth":
                if (is_numeric(KothTask::$currentKoth)) {
                    $player = KothTask::$currentPlayer;
                    $player = is_null($player) ? "Aucun joueur" : $player;

                    $remaining = Util::formatDurationFromSeconds(KothTask::$currentKoth);
                    return Util::ARROW . "Koth".Util::IARROW."\n§c" . $player . " §fcontrôle le koth actuellement\n§fTemps restant : §c" . $remaining;
                } else {
                    return Util::ARROW . "Koth".Util::IARROW."\n§fAucun event §ckoth §fn'est en cours";
                }
            case "outpost":
                if (!is_null(Cache::$data["outpost"])) {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$nextReward);
                    $faction = Faction::getFactionUpperName(Cache::$data["outpost"]);

                    return Util::ARROW . "Outpost".Util::IARROW."\n§fLa faction §c" . $faction . " §fcontrôle l'outpost\n§fRécompense dans §c" . $remaining . "\n§fPlus controlé dans §c" . OutpostTask::$currentOutpost . " §fsecondes";
                } else {
                    $remaining = Util::formatDurationFromSeconds(OutpostTask::$currentOutpost);
                    return Util::ARROW . "Outpost".Util::IARROW."\n§cAucune §ffaction ne contrôle l'outpost\n§fOutpost contrôlé dans §c" . $remaining;
                }
            case "money-zone":
                $this->period = null;
                return Util::ARROW . "Zone Money".Util::IARROW."\nReste ici et gagne §c50$ §ftoutes les §c3 §fsecondes\n§fATTENTION ! Tu dois être §cseul §fsur la platforme";
        }

        if ($name[0] === "#") {
            $text = substr($name, 1);
        } else {
            $text = "§r   \n  " . Util::stringToUnicode($name) . "  \n§r   ";
        }

        $this->period = null;
        return $text;
    }
}