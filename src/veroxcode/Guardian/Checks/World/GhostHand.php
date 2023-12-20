<?php

namespace veroxcode\Guardian\Checks\World;

use pocketmine\block\Bed;
use pocketmine\block\Chest;
use pocketmine\block\Cobweb;
use pocketmine\block\Glass;
use pocketmine\block\Grass;
use pocketmine\block\Vine;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\network\mcpe\protocol\types\InputMode;
use veroxcode\Guardian\Checks\Check;
use veroxcode\Guardian\Checks\CheckManager;
use veroxcode\Guardian\Checks\Notifier;
use veroxcode\Guardian\Checks\Punishments;
use veroxcode\Guardian\Guardian;
use veroxcode\Guardian\User\User;
use veroxcode\Guardian\Utils\Blocks;
use veroxcode\Guardian\Utils\Raycast;

class GhostHand extends Check
{

    public function __construct()
    {
        parent::__construct("GhostHand", CheckManager::WORLD);
    }

    public function onBlockBreak(BlockBreakEvent $event, User $user): void
    {

        if ($user->getInput() == 0 || $user->getInput() == InputMode::TOUCHSCREEN || $user->getTicksSinceJoin() < 100){
            return;
        }

        $block = $event->getBlock();
        $player = $user->getPlayer();

        $ping = Guardian::getInstance()->getPingManager()->getPing($player);
        $rewindTicks = ceil($ping / 50) + 20;

        if ($block instanceof Cobweb || $block instanceof Vine || !$block->isFullCube()){
            return;
        }

        for ($i = 0; $i < $rewindTicks; $i++) {
            $rewindUser = $user->rewindMovementBuffer($i);
            $distance = $rewindUser->getPosition()->distance($block->getPosition());
            $rayBlock = Raycast::getBlockOnLine($player, $rewindUser->getPosition(), $rewindUser->getDirection(), $distance + 0.3);

            if ($rayBlock != null){
                if ($rayBlock === $block){
                    $user->decreaseViolation($this->getName(), 1);
                    return;
                }
            }
        }

        $event->cancel();
        if ($user->getViolation($this->getName()) < $this->getMaxViolations()) {
            $user->increaseViolation($this->getName(), 2);
        }

        if ($user->getViolation($this->getName()) >= $this->getMaxViolations()){
            Notifier::NotifyFlag($player->getName(), $user, $this, $user->getViolation($this->getName()), $this->hasNotify());
            if ($this->getPunishment() != "Cancel") {
                Punishments::punishPlayer($this, $user, $player->getPosition());
            }
        }

    }

}