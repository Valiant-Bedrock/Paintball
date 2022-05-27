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

namespace paintball\game;

use libgame\game\GameState;
use libgame\game\round\Round;
use libgame\game\round\RoundManager;
use libgame\game\round\RoundState;
use libgame\team\Team;
use paintball\item\PaintballKits;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;

class PaintballRoundManager extends RoundManager {

	public function getRoundCount(): int {
		return 10;
	}

	public function getRoundLength(): int {
		return 60 * 5;
	}

	public function getGameWinner(): ?Team {
		foreach($this->getGame()->getTeamManager()->getAll() as $team) {
			if($this->hasTeamWon($team)) {
				return $team;
			}
		}
		return null;
	}

	public function hasTeamWon(Team $team): bool {
		return $this->getScore($team) > (int) floor($this->getRoundCount() / 2);
	}

	public function handleTick(): void {
		switch($this->state->id()) {
			case RoundState::PREROUND()->id():
				$this->handleWaiting();
				break;
			case RoundState::IN_ROUND()->id():
				$this->handleInGame();
				break;
			case RoundState::POSTROUND()->id():
				$this->handleEnding();
				break;
		}
	}

	public function handleWaiting(): void {
		$this->roundCountdown--;
		$this->getGame()->broadcastTip($this->roundCountdown <= 0 ?
			TextFormat::YELLOW . "Round {$this->current->getNumber()} has started!" :
			TextFormat::YELLOW . "Round {$this->current->getNumber()} starting in $this->roundCountdown..."
		);

		if($this->roundCountdown > 0 && $this->roundCountdown <= 3) {
			$this->getGame()->broadcastSound(new ClickSound($this->roundCountdown));
		}

		if($this->roundCountdown <= 0) {
			$this->getGame()->broadcastSound(new NoteSound(
				instrument: NoteInstrument::PIANO(),
				note: 127
			));
			$this->setState(RoundState::IN_ROUND());
			$this->getGame()->executeOnPlayers(function(Player $player): void {
				$player->setImmobile(false);
				PaintballKits::VANILLA()->give($player);
			});
		}

	}

	public function handleInGame(): void {
		$winner = $this->getRoundWinner();
		if($winner !== null) {
			$this->getGame()->broadcastMessage(TextFormat::GREEN . "$winner won round {$this->getCurrentRound()->getNumber()}!");
			$this->setScore($winner, $this->getScore($winner) + 1);
			$this->setState(RoundState::POSTROUND());
		} elseif($this->getCurrentRound()->getTime() >= $this->getRoundLength() || count($this->getGame()->getTeamManager()->getAliveTeams()) === 0) {
			$this->getGame()->broadcastMessage(TextFormat::GREEN . "Round {$this->getCurrentRound()->getNumber()} ended in a draw!");
			$this->setState(RoundState::POSTROUND());
		}
		$this->getCurrentRound()->incrementTime();
	}

	public function handleEnding(): void {
		$winner = $this->getRoundWinner();
		if($this->current->getNumber() >= $this->getRoundCount()|| ($winner !== null && $this->hasTeamWon($winner))) {
			$this->getGame()->setState(GameState::POSTGAME());
			return;
		}

		foreach($this->getGame()->getArena()->getWorld()->getEntities() as $entity) {
			if(!$entity instanceof Player) {
				$entity->close();
			}
		}

		$this->getGame()->executeOnTeams(function(Team $team): void {
			/** @var PaintballGame $game */
			$game = $this->getGame();
			$game->setupTeam($team);
		});

		$this->setState(RoundState::PREROUND());
		$this->roundCountdown = self::COUNTDOWN_LENGTH;
		$this->setCurrentRound(new Round(number: $this->current->getNumber() + 1));
	}
}