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
use libgame\team\Team;
use paintball\arena\PaintballArena;
use paintball\game\PaintballGame;
use pocketmine\math\Vector3;

class StartingStateHandler extends GameStateHandler {

	public function handleSetup(): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();
		/** @var PaintballArena $arena */
		$arena = $game->getArena();
		// Setup spawnpoints
		$availableSpawnpoints = [$arena->getFirstSpawnpoint(), $arena->getSecondSpawnpoint()];
		$game->executeOnTeams(function (Team $team) use ($game, &$availableSpawnpoints) {
			// Picks a random spawnpoint
			$spawnpointIndex = array_rand($availableSpawnpoints);
			/** @var Vector3 $spawnpoint */
			$spawnpoint = ($availableSpawnpoints[$spawnpointIndex])->add(0, 2, 0);
			// Sets the team's spawnpoint
			$game->setTeamSpawnpoint($team, $spawnpoint);
			// Removes the spawnpoint from the available spawnpoints
			unset($availableSpawnpoints[$spawnpointIndex]);

			// Configures the team
			$game->setupTeam($team);
		});
		$game->setState(GameState::IN_GAME());
	}

	public function handleTick(int $currentStateTime): void {
	}

	public function handleFinish(): void {}
}