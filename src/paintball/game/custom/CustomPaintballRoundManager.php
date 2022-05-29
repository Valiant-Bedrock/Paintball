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

namespace paintball\game\custom;

use libgame\game\RoundBasedGame;
use paintball\game\PaintballRoundManager;

class CustomPaintballRoundManager extends PaintballRoundManager {

	public const DEFAULT_ROUND_COUNT = 10;

	public function __construct(RoundBasedGame $game, protected int $roundCount = self::DEFAULT_ROUND_COUNT) {
		parent::__construct($game);
	}

	public function getRoundCount(): int {
		return $this->roundCount;
	}

	public function setRoundCount(int $roundCount): void {
		$this->roundCount = $roundCount;
	}

}