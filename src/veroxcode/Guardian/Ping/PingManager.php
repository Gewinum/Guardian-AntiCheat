<?php

namespace veroxcode\Guardian\Ping;

use pocketmine\player\Player;
use pocketmine\utils\Utils;

final class PingManager
{
    private \Closure $pingGetClosure;

    public function __construct()
    {
        $this->setPingMethod(function(Player $player) : int {
            return $player->getNetworkSession()->getPing();
        });
    }

    public function getPing(Player $player): int
    {
        return ($this->pingGetClosure)($player);
    }

    public function setPingMethod(\Closure $closure): void
    {
        Utils::validateCallableSignature(function(Player $player): int { return 0; }, $closure);
        $this->pingGetClosure = $closure;
    }
}