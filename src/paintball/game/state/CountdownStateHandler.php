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

namespace paintball\game\state;

use libgame\game\GameState;
use libgame\game\GameStateHandler;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CountdownStateHandler extends GameStateHandler {

	const MAX_HEALTH = 1;
	const DURATION = 60;

	public function handleSetup(): void {
		$this->getGame()->executeOnAll(function (Player $player): void {
			$player->teleport($this->getGame()->getArena()->getWorld()->getSpawnLocation());
			$player->setGamemode(GameMode::ADVENTURE());

			// Set health
			$player->setMaxHealth(self::MAX_HEALTH);
			$player->setHealth($player->getMaxHealth());

			$player->setImmobile();

			$player->getHungerManager()->setEnabled(false);
		});
	}

	public function handleTick(int $currentStateTime): void {
		$this->getGame()->executeOnAll(function (Player $player) use($currentStateTime): void {
			$scoreboard = $this->getGame()->getScoreboardManager()->get($player);
			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::WHITE . "Game starting in " . TextFormat::YELLOW . (self::DURATION - $currentStateTime) . TextFormat::WHITE . "...",
				2 => "------------------",
				3 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});

		if($currentStateTime >= self::DURATION) {
			$this->getGame()->setState(GameState::IN_GAME());
		}
	}

	public function handleFinish(): void {}
}