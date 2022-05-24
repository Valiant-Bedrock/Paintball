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

use libgame\game\Game;
use libgame\game\GameStateHandler;
use paintball\game\PaintballGame;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class WaitingStateHandler extends GameStateHandler {

	public function getGame(): PaintballGame|Game {
		return $this->game;
	}

	public function handleSetup(): void {

	}

	public function handleTick(int $currentStateTime): void {
		$this->getGame()->executeOnAll(function (Player $player): void {
			$scoreboard = $this->getGame()->getScoreboardManager()->get($player);
			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::GREEN . "Waiting for players...",
				2 => "------------------",
				3 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});
	}

	public function handleFinish(): void {

	}
}