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
use libgame\team\Team;
use paintball\arena\PaintballArena;
use paintball\game\PaintballGame;
use paintball\PaintballBase;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;

class PostgameStateHandler extends GameStateHandler {

	public const DURATION = 30;

	public function handleSetup(): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();

		$teamManager = $game->getTeamManager();
		$roundManager = $game->getRoundManager();

		$firstTeam = $teamManager->get(1) ?? throw new AssumptionFailedError("No team 1");
		$firstScore = $roundManager->getScore($firstTeam);
		$secondTeam = $teamManager->get(2) ?? throw new AssumptionFailedError("No team 2");
		$secondScore = $roundManager->getScore($secondTeam);


		if($firstScore === $secondScore) {
			$game->broadcastMessage(TextFormat::YELLOW . "The game has ended in a draw!");
		} else {
			$winner = $firstScore > $secondScore ? $firstTeam : $secondTeam;
			$game->broadcastMessage(TextFormat::GREEN . "The game has ended! " . $winner->getFormattedName() . TextFormat::YELLOW . " has won!");
		}
	}

	public function handleTick(int $currentStateTime): void {
		$this->getGame()->executeOnAll(function (Player $player) use($currentStateTime): void {
			$scoreboard = $this->getGame()->getScoreboardManager()->get($player);
			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::GREEN . "Game ending in " . (self::DURATION - $currentStateTime) . "...",
				2 => "------------------",
				3 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});
		if($currentStateTime >= self::DURATION) {
			/** @var PaintballArena $arena */
			$arena = $this->getGame()->getArena();
			// Delete game
			$this->getGame()->getHeartbeat()->cancel();
			$this->getGame()->getPlugin()->getGameManager()->remove($this->getGame());

			$this->getGame()->executeOnAll(function(Player $player): void {
				$player->teleport($this->getGame()->getPlugin()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
				if($this->getGame()->getSpectatorManager()->isSpectator($player)) {
					$this->getGame()->getSpectatorManager()->remove($player);
				}
				if($this->getGame()->isUnassociatedPlayer($player)) {
					$this->getGame()->removeUnassociatedPlayer($player);
				}
				$scoreboard = $this->getGame()->getScoreboardManager()->get($player);
				$scoreboard->remove();
				$this->getGame()->getScoreboardManager()->remove($player);
			});

			$this->getGame()->executeOnTeams(function(Team $team): void { $this->getGame()->getTeamManager()->remove($team); });
			PaintballBase::getInstance()->getArenaManager()->setOccupied($arena, false);
		}
	}

	public function handleFinish(): void {

	}
}