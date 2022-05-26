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

namespace paintball;

use libgame\team\Team;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class TeamFlagEntity extends Living {

	public function __construct(Location $location, protected Team $team, ?CompoundTag $nbt = null) {
		parent::__construct($location, $nbt);
		$this->setNameTagAlwaysVisible();
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(
			height: 1,
			width: 1
		);
	}

	public function hasMovementUpdate(): bool {
		return false;
	}

	public function getTeam(): Team {
		return $this->team;
	}

	public static function getNetworkTypeId(): string {
		return EntityIds::SLIME;
	}

	public function getName(): string {
		return "Team Flag";
	}
}