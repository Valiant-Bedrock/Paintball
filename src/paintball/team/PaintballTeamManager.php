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

namespace paintball\team;

use libgame\team\TeamManager;
use libgame\team\TeamState;

class PaintballTeamManager extends TeamManager {

	public function setupTeamStates(): void {
		foreach($this->teams as $team) {
			$this->states[$team->getId()] = TeamState::create($team);
		}
	}

}