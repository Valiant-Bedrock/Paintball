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

use pocketmine\utils\EnumTrait;

/**
 * @method static RoundState WAITING()
 * @method static RoundState IN_GAME()
 * @method static RoundState ENDING()
 */
class RoundState {
	use EnumTrait;

	protected static function setup(): void {
		self::register(new RoundState("waiting"));
		self::register(new RoundState("in_game"));
		self::register(new RoundState("ending"));
	}
}