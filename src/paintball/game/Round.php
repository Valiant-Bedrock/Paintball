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

class Round {

	public function __construct(
		protected int $roundNumber,
		protected int $roundTime = 0,
	) {
	}

	public function getRoundNumber(): int {
		return $this->roundNumber;
	}

	public function incrementTime(): void {
		$this->roundTime++;
	}

	public function formatTime(): string {
		return gmdate("i:s", $this->roundTime);
	}

}