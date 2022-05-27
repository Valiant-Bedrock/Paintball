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

use Closure;
use libgame\event\GameEvent;
use libgame\game\GameState;
use libgame\game\round\RoundState;
use libgame\handler\EventHandler;
use paintball\entity\FlagEntity;
use paintball\event\PlayerDeathEvent;
use paintball\game\custom\CustomPaintballGame;
use paintball\game\PaintballGame;
use paintball\utils\Icons;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\XpLevelUpSound;

class PaintballEventHandler extends EventHandler {

	public function __construct(protected PaintballGame $game) {

	}

	protected function shouldHandle(Event $event): bool {
		if($event instanceof GameEvent) {
			return $event->getGame() === $this->game;
		} elseif($event instanceof EntityDamageEvent) {
			$entity = $event->getEntity();
			return ($entity instanceof Player && $this->game->isInGame($entity)) || $entity->getWorld() === $this->game->getArena()->getWorld();
		} elseif($event instanceof PlayerEvent) {
			return $this->game->isInGame($event->getPlayer());
		}
		return false;
	}

	public function handleQuit(PlayerQuitEvent $event): void {
		$player = $event->getPlayer();
		$this->game->handleQuit($player);
	}

	public function handleDropItem(PlayerDropItemEvent $event): void {
		$event->cancel();
	}

	public function handleItemUse(PlayerItemUseEvent $event): void {
		$player = $event->getPlayer();
		if($this->game instanceof CustomPaintballGame) {
			$menu = $this->game->getLeader() === $player ? $this->game->getLeaderHotbarMenu() : $this->game->getPlayerHotbarMenu();
			$menu->checkAndCallItem($event);
		}
	}

	public function handleEntityDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();
		if($entity instanceof Player) {
			if($this->game->getState()->equals(GameState::WAITING())) {
				$event->cancel();
				return;
			}
			$team = $this->game->getTeamManager()->getTeam($entity);
			if($team === null) {
				return;
			}
			if(!$event instanceof EntityDamageByChildEntityEvent) {
				$event->cancel();
				return;
			}

			$damager = $event->getDamager();
			assert($damager instanceof Player);
			if($this->game->getTeamManager()->checkOnTeams($entity, $damager)) {
				$event->cancel();
				return;
			}

			$distance = $damager->getPosition()->distance($entity->getPosition());
			$this->game->getArena()->getWorld()->addSound($damager->getPosition(), new XpLevelUpSound((int) floor($distance)), [$damager]);

			$event->cancel();
			$entity->setLastDamageCause($event);

			$deathEvent = new PlayerDeathEvent($entity);
			$deathEvent->call();
			$this->game->broadcastMessage(TextFormat::RED . "Death > " . $deathEvent->getDeathMessage(), false);
		} elseif($entity instanceof FlagEntity && $event instanceof EntityDamageByEntityEvent) {
			$event->cancel();
			if($event instanceof EntityDamageByChildEntityEvent) {
				return;
			}
			$damager = $event->getDamager();
			assert($damager instanceof Player);
			$team = $entity->getTeam();
			if($this->game->getTeamManager()->getTeam($damager) !== $team && $this->game->getRoundManager()->getState()->equals(RoundState::IN_ROUND())) {
				$team->executeOnPlayers(Closure::fromCallable([$this->game, "kill"]));
				$this->game->broadcastMessage(TextFormat::YELLOW  . "{$damager->getName()} has destroyed $team's flag!");
				$this->game->broadcastSound(new ExplodeSound());
				$entity->close();
			}
		}
	}

	public function handlePlayerDeath(PlayerDeathEvent $event): void {
		$cause = $event->getDamageCause();
		/** @var Player $damager */
		if($cause instanceof EntityDamageByChildEntityEvent && ($damager = $cause->getDamager()) instanceof Player) {
			$damager->sendTip(TextFormat::GREEN . "Eliminated {$event->getPlayer()->getName()}");
			$event->getPlayer()->sendTip(TextFormat::YELLOW . "Eliminated by {$damager->getName()}");
			$event->setDeathMessage(TextFormat::YELLOW . $damager->getName() . " " . Icons::DEATH . TextFormat::RESET . " " .TextFormat::YELLOW . $event->getPlayer()->getName());
		} else {
			$event->setDeathMessage(Icons::DEATH . TextFormat::RESET . TextFormat::YELLOW . $event->getPlayer()->getName());
		}
		$this->game->kill($event->getPlayer());
	}

	/**
	 * @param PlayerChatEvent $event
	 * @return void
	 */
	public function handleChat(PlayerChatEvent $event): void {
		$event->cancel();
		$player = $event->getPlayer();
		$team = $this->game->getTeamManager()->getTeam($player);
		$this->game->broadcastMessage(
			message: match(true) {
				$this->game->getSpectatorManager()->isSpectator($player) => TextFormat::GRAY . "[SPECTATOR]" . TextFormat::RESET . TextFormat::WHITE . $event->getFormat(),
				default => ($team !== null ? $team->getColor() . "[$team] " : "") . TextFormat::WHITE . $event->getFormat()
			},
			prependPrefix: false
		);
	}
}