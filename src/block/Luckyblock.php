<?php

namespace Faction\block;

use Faction\command\player\RandomTp;
use Faction\command\player\XpBottle;
use Faction\handler\Faction;
use Faction\handler\trait\CooldownTrait;
use Faction\item\CreeperEgg;
use Faction\Session;
use Faction\Util;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\Block as PmBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\particle\AngryVillagerParticle;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\ClickSound;

class Luckyblock extends Block
{
    use CooldownTrait;

    public function onBreak(BlockBreakEvent $event): bool
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $position = $block->getPosition();
        $world = $position->getWorld();

        [$name, $reward] = $this->getReward();

        switch (true) {
            case $reward instanceof Item:
                $player->sendMessage(Util::ARROW . "Vous avez gagné " . $name);
                $world->dropItem($position->add(0.5, 3, 0.5), $reward);
                break;
            case is_int($reward):
                $player->sendMessage("§c§l+ §r§f" . $reward . "§c$");
                $player->sendPopup("§c§l+ §r§f" . $reward . "§c$");
                Session::get($player)->addValue("money", $reward);
                break;
            case $reward instanceof EffectInstance:
                $player->sendMessage(Util::ARROW . "Vous avez reçu l'effet " . $name);
                $player->getEffects()->add($reward);
                break;
            case is_null($reward):
                $player->sendMessage(Util::ARROW . "Rien !");
                $player->sendPopup(Util::ARROW . "Rien !" . Util::IARROW);
                break;
            case $reward === "rtp":
                $player->teleport(RandomTp::generatePos());
                $player->sendMessage(Util::ARROW . "Vous avez été téléporté à un endroit aléatoire sur la map");
                break;
            case $reward === "tnt":
                $entity = CreeperEgg::createEntity($world, $position->add(0.5, 0.5, 0.5), lcg_value() * 360, 0);
                $entity->spawnToAll();

                $player->sendMessage(Util::ARROW . "ATTENTION CA VA EXPLOSER !");
                $player->sendPopup(Util::ARROW . "Attention !" . Util::IARROW);
                break;
            case $reward === "prison":
                $player->sendMessage(Util::ARROW . "Vous avez été mis en prison");
                $this->createPrison($player);
                break;
            case $reward === "lava":
                $player->sendMessage(Util::ARROW . "Vous avez été mis dans un ocean de lave");
                $this->createLava($player);
                break;
            case $reward === "pumpkin":
                $player->dropItem($player->getArmorInventory()->getHelmet());
                $player->getArmorInventory()->setHelmet(VanillaBlocks::PUMPKIN()->asItem());
                $player->sendMessage(Util::ARROW . "OuUUuuooUuuUHHHH il fait noir la dedans??");
                break;
        }

        $player->broadcastSound(new BlazeShootSound());
        $this->createAnimation($position);

        return parent::onBreak($event);
    }

    public function getReward(): mixed
    {
        $rewards = $this->getRewards();

        $totalWeight = array_sum(array_map("floatval", array_keys($rewards)));
        $randomWeight = mt_rand(0, floor($totalWeight * 100)) / 100;

        $currentWeight = 0;

        foreach ($rewards as $weight => $reward) {
            $currentWeight += floatval($weight);

            if ($currentWeight >= $randomWeight) {
                return $reward;
            }
        }
        return null;
    }

    public function getRewards(): array
    {
        return [
            "2.00" => ["une §cpelle boostée", VanillaItems::IRON_SHOVEL()],
            "2.01" => ["un §carrosoir", VanillaItems::GOLDEN_HOE()],
            "4.01" => ["§c32 §fblocs d'obsidienne", VanillaBlocks::OBSIDIAN()->asItem()->setCount(32)],
            "4.02" => ["§c16 §fblocs de fer", VanillaBlocks::IRON()->asItem()->setCount(16)],
            "3.01" => ["§c64 §fblocs de fer", VanillaBlocks::IRON()->asItem()->setCount(64)],
            "3.02" => ["§c2 §fémeraudes", VanillaItems::EMERALD()->setCount(2)],
            "3.03" => ["§c6 §fémeraudes", VanillaItems::EMERALD()->setCount(6)],
            "2.06" => ["§c4 §fémeraudes", VanillaItems::EMERALD()->setCount(4)],
            "2.03" => ["§c8 §fblocs d'obsidienne en émeraude", VanillaBlocks::CRYING_OBSIDIAN()->asItem()->setCount(8)],
            "5.01" => ["§c24 §fdiamants", VanillaItems::DIAMOND()->setCount(24)],
            "4.03" => ["§c48 §fdiamants", VanillaItems::DIAMOND()->setCount(48)],
            "4.04" => ["une §ctable d'enchantment", VanillaBlocks::ENCHANTING_TABLE()->asItem()],
            "4.05" => ["une §cenclume", VanillaBlocks::ANVIL()->asItem()],
            "2.04" => ["une §cnetherstar", VanillaItems::NETHER_STAR()],
            "4.06" => ["une §cboussole", VanillaItems::COMPASS()],
            "5.03" => ["§c64 §fcharbons", VanillaItems::COAL()->setCount(64)],
            "2.05" => ["§c3 §foeufs de creeper ", (StringToItemParser::getInstance()->parse("creeper_spawn_egg") ?? VanillaItems::AIR())->setCount(3)],
            "6.02" => ["1000$", 1000],
            "6.01" => ["5000$", 5000],
            "4.07" => ["une §cbouteille d'xp §fde §c20 §fniveaux", XpBottle::createXpBottle(20)],
            "4.09" => ["une §cbouteille d'xp §fde §c5 §fniveaux", XpBottle::createXpBottle(5)],
            "3.09" => ["§cinvisible §fpendant §c4 §fminutes", new EffectInstance(VanillaEffects::INVISIBILITY(), 4 * 20 * 60, 0)],
            "3.08" => ["§clenteur §fpendant §cune §fminute", new EffectInstance(VanillaEffects::SLOWNESS(), 20 * 60, 3)],
            "3.07" => ["votre casque est remplacé par une tête de citrouille", "pumpkin"],
            "7.06" => ["un creeper qui apparait et qui explose", "tnt"],
            "8.05" => ["un lac de lave qui apparait autour de vous", "lava"],
            "6.08" => ["une prison qui apparait autour de vous", "prison"],
            "3.04" => ["téléportation aléatoire dans la map", "rtp"],
            "7.01" => ["rien", null],
            "5.07" => ["§c64 §fblocs de pierre", VanillaBlocks::STONE()->asItem()->setCount(64)],
            "5.08" => ["§c64 §fblocs de terre", VanillaBlocks::DIRT()->asItem()->setCount(64)],
            "5.09" => ["§c64 §fblocs de chêne", VanillaBlocks::OAK_LOG()->asItem()->setCount(64)],
            "5.04" => ["un §cluckyblock", VanillaBlocks::NETHER_QUARTZ_ORE()->asItem()->setCount(1)],
            "5.05" => ["§c3 §fluckyblock", VanillaBlocks::NETHER_QUARTZ_ORE()->asItem()->setCount(3)],
            "5.06" => ["§c6 §fpomme en or", VanillaItems::GOLDEN_APPLE()->setCount(6)],
            "4.08" => ["une §cépée en émeraude", VanillaItems::GOLDEN_SWORD()],
            "4.10" => ["un §ccasque en émeraude", VanillaItems::GOLDEN_HELMET()],
            "4.11" => ["des §cbottes en émeraude", VanillaItems::GOLDEN_BOOTS()]

            // TODO AJOUTER DES POMME ZE3F
            // TODO KING KONG
            // TODO AJOUTER UNE CAPE
        ];
    }

    public function createPrison(Player $player): void
    {
        $world = $player->getWorld();
        $pos = $player->getPosition();

        for ($x = -1; $x <= 1; $x++) {
            for ($y = 0; $y <= 3; $y++) {
                for ($z = -1; $z <= 1; $z++) {
                    if (($x === 0 && $z === 0 && ($y === 1 || $y === 2)) || ($x === 0 && $z === 0 && $y === 0)) {
                        continue;
                    }

                    $newPos = Position::fromObject($pos->add($x, $y, $z), $world);
                    $currentBlock = $world->getBlock($newPos);

                    if (
                        Faction::canBuild($player, $newPos, "break") &&
                        !$currentBlock->hasSameTypeId(VanillaBlocks::BEDROCK())
                    ) {
                        $block = VanillaBlocks::IRON_BARS();
                        $world->setBlock($newPos, $block);
                    }
                }
            }
        }
    }

    public function createLava(Player $player): void
    {
        $world = $player->getWorld();
        $pos = $player->getPosition();

        for ($x = -1; $x <= 1; $x++) {
            for ($z = -1; $z <= 1; $z++) {
                $newPos = Position::fromObject($pos->add($x, 0, $z), $world);
                $currentBlock = $world->getBlock($newPos);

                if (
                    Faction::canBuild($player, $newPos, "break") &&
                    !$currentBlock->hasSameTypeId(VanillaBlocks::BEDROCK())
                ) {
                    $block = VanillaBlocks::LAVA();
                    $world->setBlock($newPos, $block);
                }
            }
        }
    }

    public function createAnimation(Position $blockPos): void
    {
        $world = $blockPos->getWorld();

        $world->addParticle($blockPos, new HugeExplodeSeedParticle());

        for ($i = 0; $i < 15; $i++) {
            $world->addParticle($blockPos->add(0.5, 1 + ($i * 0.25), 0.5), new AngryVillagerParticle());
        }

        $radius = 5;

        for ($i = 0; $i < 400; $i++) {
            $randomX = mt_rand(-$radius * 100, $radius * 100) / 100;
            $randomY = mt_rand(0, $radius * 100) / 100;
            $randomZ = mt_rand(-$radius * 100, $radius * 100) / 100;

            $randomPos = $blockPos->add($randomX, $randomY, $randomZ);
            $world->addParticle($randomPos, new HappyVillagerParticle());

            if (mt_rand(0, 20) === 5) {
                $world->addSound($randomPos, new ClickSound());
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event): bool
    {
        $player = $event->getPlayer();

        if ($event->getAction() === $event::RIGHT_CLICK_BLOCK && $player->isSneaking() && !$this->inCooldown($player)) {
            $this->sendRewardsPercentagesForm($player);
            $this->setCooldown($player, 1);
        }

        return false;
    }

    public function sendRewardsPercentagesForm(Player $player): void
    {
        $content = Util::ARROW . "Voici toutes les récompenses possible grace aux luckyblocks\n";

        foreach ($this->getRewardPercentages() as $percentage => $data) {
            $content .= "\n§c" . explode("|", $percentage)[1] . "% " . Util::ARROW . $data[0];
        }

        $form = new SimpleForm(null);
        $form->setTitle("LuckyBlock");
        $form->setContent($content);
        $player->sendForm($form);
    }

    public function getRewardPercentages(): array
    {
        $rewards = $this->getRewards();
        $result = [];

        $totalWeight = array_sum(array_map("floatval", array_keys($rewards)));
        $i = 0;

        foreach ($rewards as $weight => $reward) {
            $percentage = number_format((floatval($weight) / $totalWeight) * 100, 2);
            $result[$i . "|" . $percentage] = $reward;

            $i++;
        }

        return $result;
    }

    public function getDrops(PmBlock $block, Item $item, Player $player = null): ?array
    {
        return [];
    }

    public function getXpDropAmount(): ?int
    {
        return 0;
    }
}