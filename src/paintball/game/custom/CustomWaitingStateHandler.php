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

namespace paintball\game\custom;

use paintball\game\PaintballGame;
use paintball\game\state\WaitingStateHandler;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CustomWaitingStateHandler extends WaitingStateHandler {

	public function handleTick(int $currentStateTime): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();

		$game->executeOnAll(function (Player $player) use($game): void {
			$scoreboard = $game->getScoreboardManager()->get($player);
			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::GREEN . "Waiting for players...",
				2 => "------------------",
				3 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});
	}
}