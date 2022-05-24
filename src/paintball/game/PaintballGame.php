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

use Closure;
use libgame\Arena;
use libgame\game\Game;
use libgame\game\GameState;
use libgame\game\GameStateHandler;
use libgame\GameBase;
use libgame\team\Team;
use libgame\team\TeamMode;
use paintball\game\state\CountdownStateHandler;
use paintball\game\state\InGameStateHandler;
use paintball\game\state\PostgameStateHandler;
use paintball\game\state\WaitingStateHandler;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PaintballGame extends Game {

	public const TITLE = TextFormat::RED . TextFormat::BOLD . "VALIANT" . TextFormat::RESET . TextFormat::WHITE .  " - " . TextFormat::WHITE . "Paintball";
	public const HEARTBEAT_PERIOD = 20;
	public const ROUND_COUNT = 5;

	/** @var array<Round> */
	protected array $pastRounds = [];
	protected Round $currentRound;

	public function __construct(GameBase $plugin, string $uniqueId, Arena $arena, TeamMode $teamMode) {
		parent::__construct($plugin, $uniqueId, $arena, $teamMode, self::TITLE,self::HEARTBEAT_PERIOD);
		$this->currentRound = new Round(roundNumber: 1);
	}


	public function start(): void {
		$this->setState(GameState::COUNTDOWN());
	}

	public function setState(GameState $state): void {
		parent::setState($state);
		$this->currentStateTime = 0;
	}

	public function setupWaitingStateHandler(Game $game): GameStateHandler {
		return new WaitingStateHandler($game);
	}

	public function setupCountdownStateHandler(Game $game): GameStateHandler {
		return new CountdownStateHandler($game);
	}

	public function setupInGameStateHandler(Game $game): GameStateHandler {
		return new InGameStateHandler($game);
	}

	public function setupPostGameStateHandler(Game $game): GameStateHandler {
		return new PostgameStateHandler($game);
	}

	public function isInGame(Player $player): bool {
		return $this->getSpectatorManager()->isSpectator($player) || $this->getTeamManager()->hasTeam($player) || $this->isUnassociatedPlayer($player);
	}

	public function handleJoin(Player $player): void {
		[$id, $color] = $this->getTeamManager()->generateTeamData();
		$this->getTeamManager()->add(new Team(
			id: $id,
			color: $color,
			members: [$player]
		));
		$player->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create([
			"showCoordinates" => new BoolGameRule(true, true)
		]));
		$this->getScoreboardManager()->add($player);
	}

	public function handleQuit(Player $player): void {}

	public function getCurrentRound(): Round {
		return $this->currentRound;
	}

	public function setCurrentRound(Round $currentRound): void {
		$this->currentRound = $currentRound;
	}

	/**
	 * @return array<Round>
	 */
	public function getPastRounds(): array {
		return $this->pastRounds;
	}

	public function addPastRound(Round $round): void {
		$this->pastRounds[] = $round;
	}
}