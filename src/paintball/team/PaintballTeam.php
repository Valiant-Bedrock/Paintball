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

use libgame\team\Team;

class PaintballTeam extends Team {

	protected string $customName = "";

	/**
	 * @return string
	 */
	public function getCustomName(): string {
		return $this->customName;
	}

	public function setCustomName(string $customName): void {
		$this->customName = $customName;
	}

	public function __toString(): string {
		return $this->getCustomName() !== "" ? $this->getCustomName() : "Team {$this->getId()}";
	}

}