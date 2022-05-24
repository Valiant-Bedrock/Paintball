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

use libgame\Arena;
use libgame\team\TeamMode;
use paintball\game\PaintballGame;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;

class PaintballListener implements Listener {

	public function __construct(protected PaintballBase $plugin) {}

	public function handleJoin(PlayerJoinEvent $event): void {
		$world = $this->plugin->getServer()->getWorldManager()->getWorldByName("paintball_test");

		if(!$this->plugin->hasDefaultGame()) {
			$game = new PaintballGame(
				plugin: $this->plugin,
				uniqueId: uniqid(),
				arena: new Arena(world: $world, alignedBB: AxisAlignedBB::one()),
				teamMode: TeamMode::SOLO()
			);
			$this->plugin->getGameManager()->add($game);
			$this->plugin->setDefaultGame($game);
		} else {
			$game = $this->plugin->getDefaultGame() ?? throw new AssumptionFailedError("Default game is null");
		}

		$game->handleJoin($event->getPlayer());
	}

	public function handleEntityDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();
		if(!$entity instanceof Player) {
			return;
		}
		if($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
			$event->cancel();
		}
	}

}