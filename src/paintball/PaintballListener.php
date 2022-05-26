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

use libgame\game\Game;
use paintball\game\PaintballGame;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\World;

class PaintballListener implements Listener {

	public function __construct(protected PaintballBase $plugin) {}

	public function handleJoin(PlayerJoinEvent $event): void {
		if($this->plugin->hasDefaultGame()) {
			$game = $this->plugin->getDefaultGame() ?? throw new AssumptionFailedError("Game not found");
		} else {
			$arena = $this->plugin->getArenaManager()->findOpen();
			if($arena === null) {
				return;
			}
			$this->plugin->getArenaManager()->setOccupied($arena, true);
			$game = new PaintballGame(plugin: $this->plugin, uniqueId: uniqid(), arena: $arena);
			$this->plugin->getGameManager()->add($game);
			$this->plugin->setDefaultGame($game);
		}
		$game->handleJoin($event->getPlayer());
	}

	public function getGameByPlayer(Player $player): ?PaintballGame {
		foreach($this->plugin->getGameManager()->getAll() as $currentGame) {
			if($currentGame->isInGame($player)) {
				return $currentGame;
			}
		}
		return null;
	}

	public function getGameByWorld(World $world): ?PaintballGame {
		foreach($this->plugin->getGameManager()->getAll() as $currentGame) {
			if($currentGame->getArena()->getWorld() === $world) {
				return $currentGame;
			}
		}
		return null;
	}

	public function handleEntityDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();
		if($entity instanceof Player && ($game = $this->getGameByPlayer($entity)) !== null) {
			$game->handleEntityDamage($event);
		} elseif(($game = $this->getGameByWorld($entity->getWorld())) !== null) {
			$game->handleEntityDamage($event);
		}
	}

}