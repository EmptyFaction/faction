<?php /** @noinspection PhpUnused */

namespace Faction\listener;

use Faction\block\Durability;
use Faction\block\ExtraVanillaBlocks;
use Faction\command\player\rank\Enderchest;
use Faction\command\staff\{Ban, LastInventory, Question, Vanish};
use Faction\command\util\Bienvenue;
use Faction\entity\Creeper;
use Faction\entity\LogoutNpc;
use Faction\entity\Player as CustomPlayer;
use Faction\handler\{Cache, Faction, Jobs, Rank};
use Faction\item\ExtraVanillaItems;
use Faction\item\FarmAxe;
use Faction\Main;
use Faction\Session;
use Faction\Util;
use pocketmine\block\{Anvil,
    Barrel,
    Block,
    CartographyTable,
    Chest,
    CraftingTable,
    Crops,
    Door,
    EnchantingTable,
    FenceGate,
    Fire,
    Furnace,
    GlowLichen,
    Hopper,
    inventory\EnderChestInventory,
    Lava,
    Liquid,
    SweetBerryBush,
    Trapdoor,
    VanillaBlocks};
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\effect\{EffectInstance, VanillaEffects};
use pocketmine\event\block\{BlockBreakEvent, BlockPlaceEvent, BlockSpreadEvent};
use pocketmine\event\entity\{EntityDamageByEntityEvent, EntityDamageEvent, EntityExplodeEvent, EntityItemPickupEvent};
use pocketmine\event\inventory\{CraftItemEvent, InventoryOpenEvent, InventoryTransactionEvent, ItemDamageEvent};
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerBucketEvent,
    PlayerChatEvent,
    PlayerCreationEvent,
    PlayerDataSaveEvent,
    PlayerDeathEvent,
    PlayerInteractEvent,
    PlayerItemConsumeEvent,
    PlayerItemUseEvent,
    PlayerJoinEvent,
    PlayerMissSwingEvent,
    PlayerPreLoginEvent,
    PlayerQuitEvent,
    PlayerRespawnEvent};
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\{Axe, Bucket, Hoe, Item, PaintingItem, PotionType, Shovel, Stick, VanillaItems};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\{GameMode, Player};
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;

class EventsListener implements Listener
{
    public function onCreation(PlayerCreationEvent $event): void
    {
        $event->setPlayerClass(CustomPlayer::class);
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();

        $block = $event->getBlock();
        $item = $event->getItem();

        if (
            $event->getAction() === $event::RIGHT_CLICK_BLOCK &&
            (($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate || $block instanceof Furnace || $block instanceof SweetBerryBush || $block instanceof GlowLichen || $block instanceof CraftingTable || $block instanceof CartographyTable || $block instanceof Chest || $block instanceof Barrel || $block instanceof Hopper) || ($item instanceof Bucket || $item instanceof Hoe || ExtraVanillaItems::getItem($item) instanceof FarmAxe || $item instanceof Axe || $item instanceof Shovel || $item instanceof PaintingItem || $item instanceof Stick)) &&
            !Faction::canBuild($player, $block, "interact") &&
            !(Util::insideZone($player->getPosition(), "spawn") && ($block instanceof Anvil || $block instanceof EnchantingTable))
        ) {
            $event->cancel();

            if ($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate) {
                Util::antiBlockGlitch($player);
            }

            return;
        }

        if (!ExtraVanillaItems::getItem($item)->onInteract($event)) {
            ExtraVanillaBlocks::getBlock($block)->onInteract($event);
        }
    }

    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $message = TextFormat::clean($event->getMessage());

        $session = Session::get($player);

        if (Question::$currentEvent !== 0) {
            $valid = false;

            switch (Question::$currentEvent) {
                case 1:
                    if ($event->getMessage() === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $player->getDisplayName() . " §fa gagné §c5k$ §fen ayant réécrit le code §c" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 2:
                    if (strtolower($event->getMessage()) === Question::$currentReply) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $player->getDisplayName() . " §fa gagné §c5k$ §fen ayant trouver le mot §c" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
                case 3:
                    if ($event->getMessage() === strval(Question::$currentReply)) {
                        Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $player->getDisplayName() . " §fa gagné §c5k$ §fen ayant répondu au calcul §c" . Question::$currentReply . " §fen premier !");
                        $valid = true;
                    }
                    break;
            }

            if ($valid) {
                $event->cancel();
                $session->addValue("money", 5000);

                Question::$currentEvent = 0;
                Question::$currentReply = null;
            }
        }

        if ($session->inCooldown("chat")) {
            $event->cancel();
        } else {
            if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                $session->setCooldown("chat", 2);
            }
        }

        if (($session->data["faction_chat"] || $event->getMessage()[0] === "-") && Faction::hasFaction($player)) {
            if (!$session->data["faction_chat"]) {
                $message = substr($message, 1);
            }

            $faction = $session->data["faction"];
            $event->cancel();

            Main::getInstance()->getLogger()->info("[F] [" . $faction . "] " . $player->getName() . " » " . $message);
            Faction::broadcastFactionMessage($faction, $player->getName() . " " . Util::ARROW . $message, $session->data["ally_chat"]);

            return;
        } else if ($event->getMessage()[0] === "!" && Faction::hasFaction($player)) {
            $message = substr($message, 1);

            $faction = $session->data["faction"];
            $event->cancel();

            Main::getInstance()->getLogger()->info("[F] [" . $faction . "] " . $player->getName() . " » " . $message);
            Faction::broadcastFactionMessage($faction, $player->getName() . " " . Util::ARROW . $message, true);

            return;
        } else if ($session->inCooldown("mute")) {
            $format = Util::formatDurationFromSeconds($session->getCooldownData("mute")[0] - time());
            $player->sendMessage(Util::PREFIX . "Vous êtes mute, temps restant: §c" . $format);

            $event->cancel();
            return;
        }

        $rank = ($player->getName() === $player->getDisplayName()) ? Rank::getRank($player->getName()) : "joueur";
        $message = Rank::setReplace(Rank::getRankValue($rank, "chat"), $player, $message);

        $event->setFormatter(new LegacyRawChatFormatter($message));
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $event->setJoinMessage("");

        if (Ban::checkBan($event)) {
            return;
        }

        Main::getInstance()->getServer()->broadcastTip("§a+ " . $player->getName() . " +");

        if (Faction::hasFaction($player)) {
            Cache::$factions[$session->data["faction"]]["activity"][date("m-d")] = $player->getName();
            Faction::broadcastFactionMessage($session->data["faction"], "Le joueur de votre faction §c" . $player->getName() . " §fvient de se connecter");
        }

        foreach (Vanish::$vanish as $target) {
            $target = Main::getInstance()->getServer()->getPlayerExact($target);

            if ($target instanceof Player) {
                if ($target->hasPermission(Rank::GROUP_STAFF) || $target->getName() === $player->getName()) {
                    continue;
                }
                $target->hidePlayer($player);
            }
        }

        if (!$player->hasPlayedBefore()) {
            $path = Path::join(Main::getInstance()->getServer()->getDataPath(), "players");
            $count = count(glob($path . "/*")) + 1;

            Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $player->getName() . " §fa rejoint le serveur pour la §cpremière §ffois ! Souhaitez lui la §cbienvenue §favec la commande §c/bvn §f(#§c" . $count . "§f)!");

            Bienvenue::$alreadyWished = [];
            Bienvenue::$lastJoin = $player->getName();
        }

        $player->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(function (Inventory $inventory, int $slot, Item $oldItem): void {
            if ($inventory instanceof ArmorInventory) {
                $targetItem = $inventory->getItem($slot);

                ExtraVanillaItems::getItem($oldItem)->removeEffects($inventory);
                ExtraVanillaItems::getItem($targetItem)->addEffects($inventory);
            }
        }, null));

        Util::givePlayerPreferences($player);

        Rank::updateNameTag($player);
        Rank::addPermissions($player);
    }

    public function onRespawn(PlayerRespawnEvent $event): void
    {
        $player = $event->getPlayer();

        if (isset(Cache::$deathXp[$player->getName()])) {
            $player->getXpManager()->setCurrentTotalXp(intval(Cache::$deathXp[$player->getName()]));
            unset(Cache::$deathXp[$player->getName()]);
        }

        Util::givePlayerPreferences($event->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        Util::removeCurrentWindow($player);

        Main::getInstance()->getServer()->broadcastTip("§c- " . $player->getName() . " -");
        $event->setQuitMessage("");

        if (Util::getTpTime($player) > 0) {
            $entity = new LogoutNpc($player->getLocation(), $player->getSkin());
            $entity->initEntityB($player);
            $entity->spawnToAll();
        }

        Session::get($player)->saveSessionData();
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $session = Session::get($player);

        $event->setDeathMessage("");

        $killstreak = $session->data["killstreak"];
        $format = $player->getPosition()->getFloorX() . ":" . $player->getPosition()->getFloorY() . ":" . $player->getPosition()->getFloorZ();

        $rank = Rank::getEqualRankBySession($session);
        $keepXp = Rank::getRankValue($rank, "xp");

        $session->data["killstreak"] = 0;
        $session->data["back"] = $format;

        $session->removeCooldown("combat");
        $session->addValue("death");

        Cache::$deathXp[$player->getName()] = intval($player->getXpManager()->getCurrentTotalXp() * ($keepXp / 100));
        $event->setXpDropAmount(intval($event->getXpDropAmount() * ((100 - $keepXp) / 100)));

        $cause = $player->getLastDamageCause();

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();

            if ($damager instanceof Player) {
                LastInventory::saveOnlineInventory($player, $damager, $killstreak);

                $pot1 = Util::getItemCount($player, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));
                $pot2 = Util::getItemCount($damager, VanillaItems::SPLASH_POTION()->setType(PotionType::STRONG_HEALING()));

                Main::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") a été tué par " . $damager->getDisplayName() . " (" . $damager->getName() . ")");
                Main::getInstance()->getServer()->broadcastMessage(Util::PREFIX . "§c" . $player->getDisplayName() . "[§7" . $pot1 . "§c] §fa été tué par le joueur §c" . $damager->getDisplayName() . "[§7" . $pot2 . "§c]");

                $damagerSession = Session::get($damager);

                $damagerSession->addValue("kill");
                $damagerSession->addValue("killstreak");

                if (Faction::hasFaction($damager)) Faction::addPower($damagerSession->data["faction"], 2);
                if (Faction::hasFaction($player)) Faction::addPower($session->data["faction"], -mt_rand(3, 4));

                Jobs::addXp($damager, "Hunter", 50 + $damagerSession->data["killstreak"]);
                return;
            }
        } else {
            Main::getInstance()->getServer()->broadcastPopup("");
            Main::getInstance()->getLogger()->info($player->getDisplayName() . " (" . $player->getName() . ") est mort");
        }

        LastInventory::saveOnlineInventory($player, null, $killstreak);
    }

    public function onItemDamage(ItemDamageEvent $event): void
    {
        ExtraVanillaItems::getItem($event->getItem())->onDamage($event);
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0) {
            $event->cancel();
            return;
        } else if (!$entity instanceof Player) {
            return;
        }

        $entitySession = Session::get($entity);

        if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $entity->teleport($entity->getPosition()->getWorld()->getSpawnLocation());
            $event->cancel();
            return;
        } else if (
            $entitySession->inCooldown("invincibility") ||
            $event->getCause() === EntityDamageEvent::CAUSE_FALL ||
            $event->getCause() === EntityDamageEvent::CAUSE_LAVA ||
            Util::insideZone($entity->getPosition(), "spawn") ||
            $entitySession->data["staff_mod"][0]
        ) {
            $event->cancel();
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($damager instanceof Player) {
                if (Util::insideZone($damager->getPosition(), "spawn")) {
                    $event->cancel();
                    return;
                }

                $damagerSession = Session::get($damager);

                if ($damagerSession->inCooldown("invincibility") || $event->isCancelled() || $entity->isFlying() || $entity->getAllowFlight() || $entity->getGamemode() === GameMode::CREATIVE() || $damager->getGamemode() === GameMode::CREATIVE() || $entity->hasNoClientPredictions()) {
                    $event->cancel();
                    return;
                }

                $entityFaction = $entitySession->data["faction"];
                $damagerFaction = $damagerSession->data["faction"];

                if (!is_null($entityFaction) && ($entityFaction === $damagerFaction || Faction::getAlly($entityFaction) === $damagerFaction)) {
                    $event->cancel();
                    return;
                }

                $damagerSession->setCooldown("combat", 30, [$entity->getName()]);
                $entitySession->setCooldown("combat", 30, [$damager->getName()]);

                $event->setKnockback(0.38);
                $event->setAttackCooldown(8);
            }
        }
    }

    /**
     * @handleCancelled
     */
    public function onUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();

        $session = Session::get($player);

        if ($session->data["staff_mod"][0]) {
            $command = match ($item->getCustomName()) {
                "§r" . Util::PREFIX . "Vanish §c§l«" => "/vanish",
                "§r" . Util::PREFIX . "Random Tp §c§l«" => "/randomtp",
                "§r" . Util::PREFIX . "Spectateur §c§l«" => "/spec",
                default => null
            };

            if ($command !== null) {
                $player->chat($command);
            }
        }

        if ($event->isCancelled()) {
            return;
        }

        ExtraVanillaItems::getItem($item)->onUse($event);
    }

    public function onPick(EntityItemPickupEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            if (Session::get($entity)->data["staff_mod"][0]) {
                $event->cancel();
            }
        }
    }

    public function onTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        $staff = Session::get($player)->data["staff_mod"][0];

        foreach ($transaction->getActions() as $action) {
            $sourceItem = $action->getSourceItem();
            $targetItem = $action->getTargetItem();

            if ($action instanceof SlotChangeAction && ($staff || $player->hasNoClientPredictions())) {
                $event->cancel();
                return;
            }

            $nbt = ($sourceItem->getNamedTag() ?? new CompoundTag());
            $_nbt = ($targetItem->getNamedTag() ?? new CompoundTag());

            foreach ($transaction->getInventories() as $inventory) {
                if ($inventory instanceof EnderChestInventory) {
                    if (($nbt->getTag("enderchest_slots") && $nbt->getString("enderchest_slots") === "restricted") || ($_nbt->getTag("enderchest_slots") && $_nbt->getString("enderchest_slots") === "restricted")) {
                        $event->cancel();
                        return;
                    }
                }
            }
        }
    }

    public function onOpenInventory(InventoryOpenEvent $event): void
    {
        $player = $event->getPlayer();
        $inventory = $event->getInventory();

        if ($inventory instanceof EnderChestInventory) {
            Enderchest::setEnderchestGlass($player, $inventory);
        }
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $block = null;

        if (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
            return;
        }

        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $transactionBlock]) {
            $block = $transactionBlock;

            if (!$transactionBlock instanceof Block) {
                continue;
            }

            if (!Faction::canBuild($player, $transactionBlock, "place")) {
                Util::antiBlockGlitch($player);

                $event->cancel();
                return;
            }

            $position = $transactionBlock->getPosition();
            $format = $position->__toString();

            if (isset(Cache::$durability[$format])) {
                unset(Cache::$durability[$format]);
            }
        }

        if ($block instanceof Block) {
            ExtraVanillaBlocks::getBlock($block)->onPlace($event);
        }
    }

    public function onSpread(BlockSpreadEvent $event): void
    {
        $source = $event->getSource();

        $sourcePos = $source->getPosition();
        $blockPos = $event->getBlock()->getPosition();

        if ($source instanceof Fire || $event->getBlock() instanceof Fire) {
            $event->cancel();
            return;
        }

        if ($source instanceof Lava && $sourcePos->getY() !== $blockPos->getY()) {
            $event->cancel();
        } else if ($source instanceof Liquid && $blockPos->getWorld() === Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()) {
            if (
                Util::insideZone($blockPos, "warzone") ||
                Faction::inClaim($sourcePos->getX(), $sourcePos->getZ())[1] !== Faction::inClaim($blockPos->getX(), $blockPos->getZ())[1]
            ) {
                $event->cancel();
            }
        }
    }

    public function onBucket(PlayerBucketEvent $event): void
    {
        $player = $event->getPlayer();

        if (!Faction::canBuild($player, $event->getBlockClicked(), "place")) {
            $event->cancel();
        } else if (Session::get($player)->data["staff_mod"][0]) {
            $event->cancel();
        }
    }

    public function onMissSwing(PlayerMissSwingEvent $event): void
    {
        $event->getPlayer()->broadcastAnimation(new ArmSwingAnimation($event->getPlayer()), $event->getPlayer()->getViewers());
        $event->cancel();
    }

    public function onCraft(CraftItemEvent $event): void
    {
        $input = $event->getInputs();
        $player = $event->getPlayer();

        foreach ($input as $item) {
            if (!is_null($item->getNamedTag()->getTag("menu_item"))) {
                $event->cancel();
                Util::removeCurrentWindow($player);
                break;
            }
        }
    }

    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $session = Session::get($player);

        if ($session->data["staff_mod"][0]) {
            $event->cancel();
            return;
        } else if (!Faction::canBuild($player, $block, "break")) {
            if ($block->isFullCube()) {
                Util::antiBlockGlitch($player);
            }

            $event->cancel();
            return;
        }

        if ($session->data["cobblestone"] === false && ($block->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $block->hasSameTypeId(VanillaBlocks::STONE()))) {
            $event->setDrops([]);
        }

        if (ExtraVanillaItems::getItem($event->getItem())->onBreak($event)) {
            return;
        } else if (ExtraVanillaBlocks::getBlock($event->getBlock())->onBreak($event)) {
            return;
        }

        if ($event->isCancelled()) {
            return;
        }

        if ($block->hasSameTypeId(VanillaBlocks::COBBLESTONE()) || $block->hasSameTypeId(VanillaBlocks::STONE())) {
            Jobs::addXp($player, "Mineur", 1);
        } else if ($block->hasSameTypeId(VanillaBlocks::MELON()) || ($block instanceof Crops && !$block->ticksRandomly())) {
            Jobs::addXp($player, "Farmeur", mt_rand(1, 3));
        }

        Util::addItems($player, $event->getDrops());

        if ($event->getXpDropAmount() > 0) {
            $player->getXpManager()->addXp($event->getXpDropAmount());
        }

        $event->setDrops([]);
        $event->setXpDropAmount(0);
    }

    public function onCommand(CommandEvent $event): void
    {
        $sender = $event->getSender();

        $command = explode(" ", $event->getCommand());
        Main::getInstance()->getLogger()->info("[" . $sender->getName() . "] " . implode(" ", $command));

        if ($sender instanceof Player) {
            $session = Session::get($sender);

            if ($session->inCooldown("cmd")) {
                $event->cancel();
            } else {
                if (!$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
                    $session->setCooldown("cmd", 1);
                }
            }

            if ($sender->hasNoClientPredictions()) {
                $event->cancel();
                return;
            }

            $command[0] = strtolower($command[0]);
            $event->setCommand(implode(" ", $command));
        }
    }

    public function onPlayerSave(PlayerDataSaveEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player instanceof Player) {
            $session = Session::get($player);
            $session->saveSessionData(false);
        }
    }

    public function onPreLogin(PlayerPreLoginEvent $event): void
    {
        $username = $event->getPlayerInfo()->getUsername();

        foreach (Main::getInstance()->getServer()->getWorldManager()->getDefaultWorld()->getEntities() as $entity) {
            if ($entity instanceof LogoutNpc) {
                $name = $entity->player;
                $name = is_null($name) ? "" : $name;

                if (strtolower($username) === strtolower($name)) {
                    $entity->killed = true;
                    $entity->flagForDespawn();
                }
            }
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event): void
    {
        ExtraVanillaItems::getItem($event->getItem())->onConsume($event);
    }

    public function onEntityExplode(EntityExplodeEvent $event): void
    {
        $blockList = $event->getBlockList();

        if (!$event->getEntity() instanceof Creeper) {
            return;
        }

        foreach ($blockList as $id => $block) {
            $safeBlocks = [
                VanillaBlocks::LAVA(), VanillaBlocks::WATER(),
                VanillaBlocks::OBSIDIAN(), VanillaBlocks::BEDROCK(),
                VanillaBlocks::ENCHANTING_TABLE(), // Miss command block
                VanillaBlocks::ENDER_CHEST(), VanillaBlocks::BARRIER(),
                VanillaBlocks::BARRIER(), VanillaBlocks::CRYING_OBSIDIAN()
            ];

            foreach ($safeBlocks as $key => $value) {
                if ($value instanceof Block && ExtraVanillaBlocks::getBlock($value) instanceof Durability) {
                    unset($safeBlocks[$key]);
                }
            }

            $durability = null;
            $cblock = ExtraVanillaBlocks::getBlock($block);

            $position = $block->getPosition();

            if ($cblock instanceof Durability) {
                $durability = $cblock->getDurability();
            } else {
                continue;
            }

            $include = array_reduce($safeBlocks, function ($carry, $safeBlock) use ($block) {
                return $carry || $block instanceof $safeBlock;
            }, false);

            if ($include || Util::insideZone($position, "warzone")) {
                unset($blockList[$id]);
                continue;
            }

            $format = $position->__toString();
            unset($blockList[$id]);

            $actual = Cache::$durability[$format] ?? $durability - 1;
            Cache::$durability[$format] = $actual - 1;

            if (0 >= $actual) {
                $item = null;
                $player = null;

                $position->getWorld()->useBreakOn($position->asVector3(), $item, $player, true);
                unset(Cache::$durability[$format]);
            }
        }

        $event->setBlockList($blockList);
    }

    public function onDataPacketDecode(DataPacketDecodeEvent $event): void
    {
        $packetId = $event->getPacketId();
        $packetBuffer = $event->getPacketBuffer();

        if (strlen($packetBuffer) > 8096 && $packetId !== ProtocolInfo::LOGIN_PACKET && $packetId !== ProtocolInfo::PLAYER_SKIN_PACKET) {
            $origin = $event->getOrigin();
            $event->cancel();

            Main::getInstance()->getLogger()->warning("ID de paquet non décodé: $packetId (" . strlen($packetBuffer) . ") venant de : " . $origin->getPlayer() instanceof Player ? $origin->getPlayer()->getName() : $origin->getIp());
            Main::getInstance()->getServer()->getNetwork()->blockAddress($origin->getIp(), 250);
        }
    }
}