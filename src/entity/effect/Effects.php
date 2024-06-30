<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace Faction\entity\effect;

use pocketmine\color\Color;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\data\bedrock\EffectIds;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\lang\KnownTranslationFactory;

class Effects
{
    public static SlowFalling $slowFalling;

    public function __construct()
    {
        self::$slowFalling = new SlowFalling(
            KnownTranslationFactory::potion_slowFalling(),
            new Color(0xff, 0xef, 0xd1)
        );

        EffectIdMap::getInstance()->register(EffectIds::SLOW_FALLING, self::$slowFalling);
        StringToEffectParser::getInstance()->register("slow_falling", fn() => self::$slowFalling);
    }
}