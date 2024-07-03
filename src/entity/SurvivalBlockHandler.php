<?php

namespace Faction\entity;

use Faction\item\ExtraVanillaItems;
use Faction\item\FarmAxe;
use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockToolType;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ToolTier;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\particle\BlockPunchParticle;
use pocketmine\world\sound\BlockPunchSound;
use function abs;

final class SurvivalBlockHandler
{
    public const DEFAULT_FX_INTERVAL_TICKS = 5;

    private int $fxTicker = 0;
    private float $breakSpeed;
    private float $breakProgress = 0;
    private int $targetedFace;

    public function __construct(
        private readonly Player  $player,
        private readonly Vector3 $blockPos,
        private readonly Block   $block,
        int                      $targetedFace,
        private readonly int     $maxPlayerDistance,
        private readonly int     $fxTickInterval = self::DEFAULT_FX_INTERVAL_TICKS
    )
    {
        $this->targetedFace = $targetedFace;
        $this->breakSpeed = $this->calculateBreakProgressPerTick();

        if ($this->breakSpeed > 0) {
            $this->player->getWorld()->broadcastPacketToViewers(
                $this->blockPos,
                LevelEventPacket::create(LevelEvent::BLOCK_START_BREAK, (int)(65535 * $this->breakSpeed), $this->blockPos)
            );
        }
    }

    /**
     * Returns the calculated break speed as percentage progress per game tick.
     */
    private function calculateBreakProgressPerTick(): float
    {
        if (!$this->block->getBreakInfo()->isBreakable()) {
            return 0.0;
        }

        $breakTimePerTick = $this->getBreakTime($this->block->getBreakInfo(), $this->player->getInventory()->getItemInHand()) * 20;

        if ($breakTimePerTick > 0) {
            return 1 / $breakTimePerTick;
        }
        return 1;
    }

    public function getBreakTime(BlockBreakInfo $breakInfo, Item $item): float
    {
        $base = $breakInfo->getHardness();

        if ($this->isToolCompatible($breakInfo, $item)) {
            $base *= BlockBreakInfo::COMPATIBLE_TOOL_MULTIPLIER;
        } else {
            $base *= BlockBreakInfo::INCOMPATIBLE_TOOL_MULTIPLIER;
        }

        $extraItem = ExtraVanillaItems::getItem($item);

        if ($extraItem instanceof FarmAxe) {
            $efficiency = $this->getMiningEfficiency(
                true,
                $extraItem->getBaseEfficiency(),
                $item,
            );
        } else {
            $efficiency = $item->getMiningEfficiency(($breakInfo->getToolType() & $item->getBlockToolType()) !== 0);
        }

        if ($efficiency <= 0) {
            throw new InvalidArgumentException(get_class($item) . " has invalid mining efficiency: expected >= 0, got $efficiency");
        }

        $base /= $efficiency;
        return max(0.051, $base);
    }

    public function isToolCompatible(BlockBreakInfo $breakInfo, Item $tool): bool
    {
        if ($breakInfo->getHardness() < 0) {
            return false;
        }

        if (ExtraVanillaItems::getItem($tool) instanceof FarmAxe) {
            $toolType = BlockToolType::PICKAXE;
            $harvestLevel = ToolTier::NETHERITE()->getHarvestLevel();
        } else {
            $toolType = $tool->getBlockToolType();
            $harvestLevel = $tool->getBlockToolHarvestLevel();
        }

        return $breakInfo->getToolType() === BlockToolType::NONE || $breakInfo->getToolHarvestLevel() === 0 || (($breakInfo->getToolType() & $toolType) !== 0 && $harvestLevel >= $breakInfo->getToolHarvestLevel());
    }

    public function getMiningEfficiency(bool $isCorrectTool, int $baseEfficiency, Item $item): float
    {
        $efficiency = 1;
        if ($isCorrectTool) {
            $efficiency = $baseEfficiency;

            if (($enchantmentLevel = $item->getEnchantmentLevel(VanillaEnchantments::EFFICIENCY())) > 0) {
                $efficiency += ($enchantmentLevel ** 2 + 1);
            }
        }

        return $efficiency;
    }

    public function update(): bool
    {
        if ($this->player->getPosition()->distanceSquared($this->blockPos->add(0.5, 0.5, 0.5)) > $this->maxPlayerDistance ** 2) {
            return false;
        }

        $newBreakSpeed = $this->calculateBreakProgressPerTick();

        if (abs($newBreakSpeed - $this->breakSpeed) > 0.0001) {
            $this->breakSpeed = $newBreakSpeed;
            //TODO: sync with client
        }

        $this->breakProgress += $this->breakSpeed;

        if (($this->fxTicker++ % $this->fxTickInterval) === 0 && $this->breakProgress < 1) {
            $this->player->getWorld()->addParticle($this->blockPos, new BlockPunchParticle($this->block, $this->targetedFace));
            $this->player->getWorld()->addSound($this->blockPos, new BlockPunchSound($this->block));
            $this->player->broadcastAnimation(new ArmSwingAnimation($this->player), $this->player->getViewers());
        }

        return $this->breakProgress < 1;
    }

    public function getBlockPos(): Vector3
    {
        return $this->blockPos;
    }

    public function getTargetedFace(): int
    {
        return $this->targetedFace;
    }

    public function setTargetedFace(int $face): void
    {
        Facing::validate($face);
        $this->targetedFace = $face;
    }

    public function getBreakSpeed(): float
    {
        return $this->breakSpeed;
    }

    public function getBreakProgress(): float
    {
        return $this->breakProgress;
    }

    public function __destruct()
    {
        if ($this->player->getWorld()->isInLoadedTerrain($this->blockPos)) {
            $this->player->getWorld()->broadcastPacketToViewers(
                $this->blockPos,
                LevelEventPacket::create(LevelEvent::BLOCK_STOP_BREAK, 0, $this->blockPos)
            );
        }
    }
}
