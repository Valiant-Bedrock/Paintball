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

use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;
use paintball\arena\parser\Vector3Parser;
use paintball\arena\parser\WorldParser;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class PaintballArenaData {
	use MarshalTrait;

	/**
	 * For some reason, PHPStan has issues with attributes as well as implemented generics..?
	 * @param string $name
	 * @param World $world
	 * @param Vector3 $firstSpawnpoint
	 * @param Vector3 $secondSpawnpoint
	 */
	public function __construct(
		#[Field] protected string $name,
		/** @phpstan-ignore-next-line */
		#[Field(parser: WorldParser::class)] protected World $world,
		/** @phpstan-ignore-next-line */
		#[Field(name: "first-spawnpoint", parser: Vector3Parser::class)] protected Vector3 $firstSpawnpoint,
		/** @phpstan-ignore-next-line */
		#[Field(name: "second-spawnpoint", parser: Vector3Parser::class)] protected Vector3 $secondSpawnpoint,
	) {
	}

	public function getName(): string {
		return $this->name;
	}

	public function getWorld(): World {
		return $this->world;
	}

	public function getFirstSpawnpoint(): Vector3 {
		return $this->firstSpawnpoint;
	}

	public function getSecondSpawnpoint(): Vector3 {
		return $this->secondSpawnpoint;
	}

}