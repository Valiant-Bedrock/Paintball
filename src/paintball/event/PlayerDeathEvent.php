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

namespace paintball\event;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\Position;

class PlayerDeathEvent extends PlayerEvent {

	protected ?EntityDamageEvent $damageCause = null;

	public function __construct(Player $player) {
		$this->player = $player;
		$this->damageCause = $player->getLastDamageCause();
	}

	public function getDamageCause(): ?EntityDamageEvent {
		return $this->damageCause;
	}
}