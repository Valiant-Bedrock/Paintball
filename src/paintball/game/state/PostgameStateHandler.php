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

use libgame\event\GameWinEvent;
use libgame\game\GameStateHandler;
use paintball\game\PaintballGame;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;

class PostgameStateHandler extends GameStateHandler {

	public const DURATION = 15;

	public function handleSetup(): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();

		$teamManager = $game->getTeamManager();
		$roundManager = $game->getRoundManager();

		$teams = $teamManager->getAll();
		switch(count($teams)) {
			case 0:
				$game->broadcastMessage(TextFormat::RED . "No teams win because they all left!");
				break;
			case 1:
				$remainingTeam = $teams[array_key_first($teams)];
				$score = $roundManager->getScore($remainingTeam);
				$game->broadcastMessage(TextFormat::GREEN . "$remainingTeam wins by default with $score points!");
				break;
			case 2:
				$firstTeam = $teamManager->get(1) ?? throw new AssumptionFailedError("No team 1");
				$firstScore = $roundManager->getScore($firstTeam);
				$secondTeam = $teamManager->get(2) ?? throw new AssumptionFailedError("No team 2");
				$secondScore = $roundManager->getScore($secondTeam);
				if($firstScore === $secondScore) {
					$game->broadcastMessage(TextFormat::YELLOW . "The game has ended in a draw!");
				} else {
					$winner = $firstScore > $secondScore ? $firstTeam : $secondTeam;
					$game->broadcastMessage(TextFormat::GREEN . "The game has ended! $winner has won!");

					$event = new GameWinEvent($this->game, $winner);
					$event->call();
				}
		}

		$game->executeOnAll(function(Player $player) {
			$player->setGameMode(GameMode::ADVENTURE());
			$player->setAllowFlight(true);
			$player->setFlying(true);
		});
	}

	public function handleTick(int $currentStateTime): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();
		$game->executeOnAll(function (Player $player) use($currentStateTime, $game): void {
			$scoreboard = $game->getScoreboardManager()->get($player);
			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::GREEN . "Game ending in " . (self::DURATION - $currentStateTime) . "...",
				2 => "------------------",
				3 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});
		if($currentStateTime >= self::DURATION) {
			$game->finish();
			// Delete game
		}
	}

	public function handleFinish(): void {

	}
}