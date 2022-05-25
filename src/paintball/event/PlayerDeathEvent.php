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

	protected string $deathMessage;
	protected ?EntityDamageEvent $damageCause = null;

	protected Position $deathPosition;
	/** @var array<Item> */
	protected array $drops = [];

	public function __construct(Player $player) {
		$this->player = $player;
		$this->damageCause = $player->getLastDamageCause();
		$this->setDeathMessage(match(true) {
			$this->damageCause instanceof EntityDamageByEntityEvent => (($damager = $this->damageCause->getDamager()) instanceof Player ? $damager->getName() : "") . " ⚔ {$player->getName()}",
			default => "⚔ {$player->getName()}",
		});
	}

	public function getDeathMessage(): string {
		return $this->deathMessage;
	}

	public function setDeathMessage(string $deathMessage): void {
		$this->deathMessage = $deathMessage;
	}

	public function getDamageCause(): ?EntityDamageEvent {
		return $this->damageCause;
	}
}