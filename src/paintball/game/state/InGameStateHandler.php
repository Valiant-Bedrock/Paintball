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

use libgame\game\GameStateHandler;
use paintball\game\PaintballGame;
use paintball\game\PaintballRoundManager;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InGameStateHandler extends GameStateHandler {

	public function handleSetup(): void {
	}

	public function handleTick(int $currentStateTime): void {
		/** @var PaintballGame $game - A bandaid solution to a larger problem */
		$game = $this->getGame();
		/** @var PaintballRoundManager $roundManager */
		$roundManager = $game->getRoundManager();

		$round = $roundManager->getCurrentRound();
		$roundManager->handleTick();
		$game->executeOnAll(function (Player $player) use($round, $game): void {
			$scoreboard = $game->getScoreboardManager()->get($player);
			$firstScore = $game->getRoundManager()->getScore($game->getFirstTeam());
			$secondScore = $game->getRoundManager()->getScore($game->getSecondTeam());

			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::WHITE . "Round: " . TextFormat::YELLOW . $round->getNumber() . TextFormat::WHITE . " | " . TextFormat::WHITE . "Time: " . TextFormat::YELLOW . $round->formatTime(),
				2 => "",
				3 => TextFormat::WHITE . "{$game->getFirstTeam()}: " . TextFormat::YELLOW . $firstScore . TextFormat::WHITE . " | " . TextFormat::WHITE . "{$game->getSecondTeam()}: " . TextFormat::YELLOW . $secondScore,
				4 => "------------------",
				5 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});
	}

	public function handleFinish(): void {
	}
}