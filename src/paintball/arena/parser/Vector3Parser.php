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

namespace paintball\arena\parser;

use libMarshal\parser\Parseable;
use pocketmine\math\Vector3;

/**
 * @implements Parseable<array{x: int|float, y: int|float, z: int|float}, Vector3>
 */
class Vector3Parser implements Parseable {

	/**
	 * @param array{x: int|float, y: int|float, z: int|float} $value
	 * @return Vector3
	 */
	public function parse(mixed $value): Vector3 {
		["x" => $x, "y" => $y, "z" => $z] = $value;
		return new Vector3($x, $y, $z);
	}

	/**
	 * @param Vector3 $value
	 * @return array{x: int|float, y: int|float, z: int|float}
	 */
	public function serialize(mixed $value): array {
		return ["x" => $value->x, "y" => $value->y, "z" => $value->z];
	}
}