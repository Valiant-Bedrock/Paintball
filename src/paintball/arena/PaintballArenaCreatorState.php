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

namespace paintball\arena;

use pocketmine\utils\EnumTrait;

/**
 * @method static PaintballArenaCreatorState FIRST_SPAWNPOINT()
 * @method static PaintballArenaCreatorState SECOND_SPAWNPOINT()
 * @method static PaintballArenaCreatorState SET_NAME()
 */
class PaintballArenaCreatorState {
	use EnumTrait;

	protected static function setup(): void {
		self::register(new PaintballArenaCreatorState("first_spawnpoint"));
		self::register(new PaintballArenaCreatorState("second_spawnpoint"));
		self::register(new PaintballArenaCreatorState("set_name"));
	}

}