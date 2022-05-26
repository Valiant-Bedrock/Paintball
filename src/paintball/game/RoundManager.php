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
use libgame\team\member\MemberState;
use libgame\team\Team;
use paintball\TeamFlagEntity;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;

class RoundManager {
	public const ROUND_COUNT = 10;
	public const COUNTDOWN_LENGTH = 10;

	/** @var array<Round> */
	protected array $past = [];
	protected Round $current;

	/** @var array<int, int> */
	protected array $scores = [];

	protected int $roundCountdown = self::COUNTDOWN_LENGTH;

	protected RoundState $state;

	public function __construct(protected PaintballGame $game) {
		$this->current = new Round(roundNumber: 1);
		$this->state = RoundState::WAITING();
	}

	public function getGame(): PaintballGame {
		return $this->game;
	}

	public function getState(): RoundState {
		return $this->state;
	}

	public function getCurrentRound(): Round {
		return $this->current;
	}

	public function setCurrentRound(Round $round): void {
		$this->current = $round;
	}

	/**
	 * @return array<Round>
	 */
	public function getPastRounds(): array {
		return $this->past;
	}

	public function addPastRound(Round $round): void {
		$this->past[] = $round;
	}

	public function getScore(Team $team): int {
		return $this->scores[$team->getId()] ??= 0;
	}

	public function setScore(Team $team, int $score): void {
		$this->scores[$team->getId()] = $score;
	}

	public function handleTick(): void {
		switch($this->state->id()) {
			case RoundState::WAITING()->id():
				$this->handleWaiting();
				break;
			case RoundState::IN_GAME()->id():
				$this->handleInGame();
				break;
			case RoundState::ENDING()->id():
				$this->handleEnding();
				break;
		}
	}

	public function handleWaiting(): void {
		$this->roundCountdown--;
		$this->getGame()->broadcastTip($this->roundCountdown <= 0 ?
			TextFormat::YELLOW . "Round {$this->current->getRoundNumber()} has started!" :
			TextFormat::YELLOW . "Round {$this->current->getRoundNumber()} starting in $this->roundCountdown..."
		);

		if($this->roundCountdown > 0 && $this->roundCountdown < 3) {
			$this->getGame()->broadcastSound(new ClickSound($this->roundCountdown));
		}

		if($this->roundCountdown <= 0) {
			$this->getGame()->broadcastSound(new NoteSound(
				instrument: NoteInstrument::PIANO(),
				note: 127
			));
			$this->state = RoundState::IN_GAME();
			$this->getGame()->executeOnPlayers(function(Player $player): void {
				$player->setImmobile(false);
				["armor" => $armor, "items" => $items] = $this->getGame()->getKit();
				$player->getArmorInventory()->setContents($armor);
				$player->getInventory()->setContents($items);
			});
		}

	}

	public function getRoundWinner(): ?Team {
		$aliveTeams = $this->getGame()->getTeamManager()->getAliveTeams();
		return count($aliveTeams) === 1 ? $aliveTeams[array_key_first($aliveTeams)] : null;
	}

	public function handleInGame(): void {
		$winner = $this->getRoundWinner();
		if($winner !== null) {
			$this->getGame()->broadcastMessage(TextFormat::GREEN . "{$winner->getFormattedName()}" . TextFormat::GREEN . " won round {$this->getCurrentRound()->getRoundNumber()}!");
			$this->setScore($winner, $this->getScore($winner) + 1);
			$this->state = RoundState::ENDING();
		} elseif(count($this->getGame()->getTeamManager()->getAliveTeams()) === 0) {
			$this->getGame()->broadcastMessage(TextFormat::GREEN . "Rounded {$this->getCurrentRound()->getRoundNumber()} in a draw!");
			$this->state = RoundState::ENDING();
		}
		$this->getCurrentRound()->incrementTime();
	}

	public function handleEnding(): void {
		$winner = $this->getRoundWinner();
		assert($winner !== null);
		if($this->current->getRoundNumber() >= self::ROUND_COUNT || $this->getScore($winner) > (int) floor(self::ROUND_COUNT / 2)) {
			$this->getGame()->setState(GameState::POSTGAME());
			return;
		}

		foreach($this->getGame()->getArena()->getWorld()->getEntities() as $entity) {
			if($entity instanceof TeamFlagEntity || $entity instanceof Arrow) {
				$entity->close();
			}
		}

		$this->getGame()->executeOnTeams(function(Team $team): void {
			$spawnpoint = $this->getGame()->getTeamSpawnpoint($team->getId());
			$team->executeOnPlayers(function(Player $player) use($spawnpoint): void {
				// Set player alive again
				$this->getGame()->getTeamManager()->setPlayerState($player, MemberState::ALIVE());
				$player->teleport($spawnpoint);
				$this->setupPlayer($player);
				$player->setImmobile(true);
			});
			$world = $this->getGame()->getArena()->getWorld();
			$entity = new TeamFlagEntity(Location::fromObject($spawnpoint->add(0, 2, 0), $world), $team, null);
			$entity->setNameTag($team->getFormattedName() . "'s Flag");
			$entity->spawnToAll();
		});

		$this->state = RoundState::WAITING();
		$this->roundCountdown = self::COUNTDOWN_LENGTH;
		$this->current = new Round(roundNumber: $this->current->getRoundNumber() + 1);
	}

	public function setupPlayer(Player $player): void {
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->setGamemode(GameMode::ADVENTURE());
		$player->setHealth($player->getMaxHealth());
	}

}