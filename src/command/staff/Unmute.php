<?php /** @noinspection PhpUnused */

namespace Faction\command\staff;

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseCommand;
use Faction\handler\Cache;
use Faction\handler\discord\Discord;
use Faction\handler\discord\EmbedBuilder;
use Faction\handler\Rank;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Unmute extends BaseCommand
{
    public function __construct(PluginBase $plugin)
    {
        parent::__construct(
            $plugin,
            "unmute",
            "Redonne la parole à un joueur"
        );

        $this->setPermissions([Rank::GROUP_STAFF]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        /** @noinspection PhpDeprecationInspection */
        $target = Main::getInstance()->getServer()->getPlayerByPrefix($args["joueur"]);

        if (!$target instanceof Player) {
            $sender->sendMessage(Util::PREFIX . "Le joueur indiqué n'est pas connecté sur le serveur");
            return;
        }
        $session = Session::get($target);

        if (!$session->inCooldown("mute")) {
            $sender->sendMessage(Util::PREFIX . "Le joueur §c" . $target->getName() . " §fn'est pas mute");
            return;
        }

        $session->removeCooldown("mute");

        $sender->sendMessage(Util::PREFIX . "Vous venez de unmute §c" . $target->getName());
        $target->sendMessage(Util::PREFIX . "Vous venez d'être unmute par §c" . $sender->getName());

        $embed = new EmbedBuilder();
        $embed->setDescription("**Unmute**\n\n**Joueur**\n" . $target->getName() . "\n\n*Unmute par le staff: " . $sender->getName() . "*");
        $embed->setColor(5635925);
        Discord::send($embed, Cache::$config["sanction-webhook"]);
    }

    protected function prepare(): void
    {
        $this->registerArgument(0, new TargetPlayerArgument(false, "joueur"));
    }
}