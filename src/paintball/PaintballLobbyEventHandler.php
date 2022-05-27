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

use libgame\handler\EventHandler;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class PaintballLobbyEventHandler extends EventHandler {

	public function __construct(protected PaintballBase $plugin) {
	}

	public function getPlugin(): PaintballBase {
		return $this->plugin;
	}

	protected function shouldHandle(Event $event): bool {
		return match(true) {
			$event instanceof PlayerEvent || $event instanceof BlockBreakEvent || $event instanceof BlockPlaceEvent => $this->getPlugin()->getGameManager()->getGameByPlayer($event->getPlayer()) === null,
			default => false
		};
	}

	public function handleJoin(PlayerJoinEvent $event): void {
		$player = $event->getPlayer();
		$player->getHungerManager()->setEnabled(false);
		$this->getPlugin()->getHotbarMenu()->send($player);
	}

	public function handleQuit(PlayerQuitEvent $event): void {
		$this->getPlugin()->removeScoreboard($event->getPlayer());
	}

	public function handleDropItem(PlayerDropItemEvent $event): void {
		$event->cancel();

	}

	public function handleItemUse(PlayerItemUseEvent $event): void {
		$event->cancel();
		$this->getPlugin()->getHotbarMenu()->checkAndCallItem($event);
	}
}