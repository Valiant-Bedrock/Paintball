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
use paintball\PaintballBase;
use paintball\utils\Column;
use paintball\utils\Icons;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InGameStateHandler extends GameStateHandler {

	public function handleSetup(): void {
	}

	public function handleTick(int $currentStateTime): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();
		/** @var PaintballRoundManager $roundManager */
		$roundManager = $game->getRoundManager();

		$round = $roundManager->getCurrentRound();
		$roundManager->handleTick();
		$game->executeOnAll(function (Player $player) use($round, $game, $roundManager): void {
			$scoreboard = $game->getScoreboardManager()->get($player);

			$firstScore = $roundManager->getScore($game->getFirstTeam());
			$secondScore = $roundManager->getScore($game->getSecondTeam());

			$columns = [
				new Column([
					TextFormat::WHITE . "Round: " . TextFormat::YELLOW . $round->getNumber() . TextFormat::WHITE,
					TextFormat::WHITE . "{$game->getFirstTeam()}: " . TextFormat::YELLOW . $firstScore . TextFormat::WHITE
				]),
				new Column([
					TextFormat::WHITE . "Time: " . TextFormat::YELLOW .  $round->formatTime(),
					TextFormat::WHITE . "{$game->getSecondTeam()}: " . TextFormat::YELLOW .  $secondScore
				])
			];

			$rows = [];
			foreach($columns as $column) {
				foreach($column->format() as $index => $row) {
					$rows[$index] ??= [];
					$rows[$index][] = $row;
				}
			}

			$flattened = array_map(
				callback: fn(array $row): string => implode(" | ", $row),
				array: $rows
			);

			$scoreboard->setLines(PaintballBase::formatGameScoreboard($flattened));
		});
	}

	public function handleFinish(): void {
	}
}