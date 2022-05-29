<?php
/**
 *
 * Copyright (C) 2020 - 2022 | Matthew Jordan
 *
 * This program is private software. You may not redistribute this software, or
 * any derivative works of this software, in source or binary form, without
 * the express permission of the owner.
 *
 * @author sylvrs
 */
declare(strict_types=1);

namespace paintball;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;

class PaintballListener implements Listener {

	public function __construct(protected PaintballBase $plugin) {}


	public function handleJoin(PlayerJoinEvent $event): void {
		$event->setJoinMessage("");
		$event->getPlayer()->getHungerManager()->setEnabled(false);
	}

	public function handleQuit(PlayerQuitEvent $event): void {
		$event->setQuitMessage("");
	}

	public function handleDamage(EntityDamageEvent $event): void {
		$player = $event->getEntity();
		if($player instanceof Player && $this->plugin->getGameManager()->getGameByPlayer($player) === null) {
			$event->cancel();

			// Teleport players back to spawn if they jump into void
			if($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
				$player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()?->getSafeSpawn() ?? throw new AssumptionFailedError("Default world not found"));
			}
		}
	}

	public function handleChat(PlayerChatEvent $event): void {
		$event->setFormat(TextFormat::YELLOW . $event->getPlayer()->getName() . TextFormat::WHITE . ": " . $event->getMessage());
		$game = $this->plugin->getGameManager()->getGameByPlayer($event->getPlayer());
		if($game === null) {
			$event->setRecipients(array_filter(
				array: $this->plugin->getServer()->getOnlinePlayers(),
				callback: fn(Player $player) => $this->plugin->getGameManager()->getGameByPlayer($event->getPlayer()) === null
			));
		}
	}
}