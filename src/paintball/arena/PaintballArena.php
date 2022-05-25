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

use libgame\Arena;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\world\World;

class PaintballArena extends Arena {

	public function __construct(
		World $world,
		protected Vector3 $firstSpawnpoint,
		protected Vector3 $secondSpawnpoint
	) {
		parent::__construct($world, AxisAlignedBB::one());
	}

	public static function create(PaintballArenaData $data): PaintballArena {
		return new PaintballArena(
			world: $data->getWorld(),
			firstSpawnpoint: $data->getFirstSpawnpoint(),
			secondSpawnpoint: $data->getSecondSpawnpoint()
		);
	}

	public function getFirstSpawnpoint(): Vector3 {
		return $this->firstSpawnpoint;
	}


	public function getSecondSpawnpoint(): Vector3 {
		return $this->secondSpawnpoint;
	}

}