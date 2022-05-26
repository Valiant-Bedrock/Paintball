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

namespace paintball\entity;

use libgame\team\Team;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class FlagEntity extends Entity {

	public const SCALE = 0.5;
	public const TURN_DEGREE_AMOUNT = 3;

	public function __construct(Location $location, protected Team $team, ?CompoundTag $nbt = null) {
		parent::__construct($location, $nbt);
		$this->setNameTagAlwaysVisible();
		$this->setScale(self::SCALE);
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(
			height: self::SCALE,
			width: self::SCALE
		);
	}

	public function canSaveWithChunk(): bool {
		return false;
	}

	public function hasMovementUpdate(): bool {
		return true;
	}

	protected function tryChangeMovement(): void {
		$this->setRotation(($this->location->yaw + self::TURN_DEGREE_AMOUNT) % 360, $this->location->pitch);
	}

	public function getTeam(): Team {
		return $this->team;
	}

	public static function getNetworkTypeId(): string {
		return EntityIds::SLIME;
	}

	public function getName(): string {
		return "Flag";
	}
}