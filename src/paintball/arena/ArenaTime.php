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
 * @method static ArenaTime MORNING()
 * @method static ArenaTime DAY()
 * @method static ArenaTime EVENING()
 * @method static ArenaTime NIGHT()
 */
class ArenaTime {
	use EnumTrait {
		__construct as private Enum__construct;
	}

	protected static function setup(): void {
		self::register(new ArenaTime("morning", 0));
		self::register(new ArenaTime("day", 1000));
		self::register(new ArenaTime("evening", 12000));
		self::register(new ArenaTime("night", 13000));
	}

	public function __construct(string $enumName, protected int $time) {
		$this->Enum__construct($enumName);
	}

	public function getTime(): int {
		return $this->time;
	}

}