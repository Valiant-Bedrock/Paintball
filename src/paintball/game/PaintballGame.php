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

namespace paintball\game;

use libgame\game\Game;
use libgame\game\GameState;
use libgame\game\GameStateHandler;
use libgame\GameBase;
use libgame\team\member\MemberState;
use libgame\team\Team;
use libgame\team\TeamMode;
use paintball\arena\PaintballArena;
use paintball\event\PlayerDeathEvent;
use paintball\game\state\CountdownStateHandler;
use paintball\game\state\InGameStateHandler;
use paintball\game\state\PostgameStateHandler;
use paintball\game\state\WaitingStateHandler;
use paintball\item\CustomItems;
use paintball\TeamFlagEntity;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\sound\Sound;

class PaintballGame extends Game {

	public const TITLE = TextFormat::RED . TextFormat::BOLD . "VALIANT" . TextFormat::RESET . TextFormat::WHITE .  " - " . TextFormat::WHITE . "Paintball";
	public const HEARTBEAT_PERIOD = 20;

	public const MAX_HEALTH = 1;

	/** @var array<int, Vector3> */
	protected array $teamSpawnpoints = [];

	protected RoundManager $roundManager;

	public function __construct(GameBase $plugin, string $uniqueId, PaintballArena $arena) {
		parent::__construct($plugin, $uniqueId, $arena, TeamMode::SOLO(), self::TITLE,self::HEARTBEAT_PERIOD);
		$this->roundManager = new RoundManager($this);
	}

	public function getRoundManager(): RoundManager {
		return $this->roundManager;
	}

	public function start(): void {
		$this->setState(GameState::COUNTDOWN());
	}

	public function setState(GameState $state): void {
		parent::setState($state);
		$this->currentStateTime = 0;
	}

	public function setupWaitingStateHandler(Game $game): GameStateHandler {
		return new WaitingStateHandler($game);
	}

	public function setupCountdownStateHandler(Game $game): GameStateHandler {
		return new CountdownStateHandler($game);
	}

	public function setupInGameStateHandler(Game $game): GameStateHandler {
		return new InGameStateHandler($game);
	}

	public function setupPostGameStateHandler(Game $game): GameStateHandler {
		return new PostgameStateHandler($game);
	}

	public function isInGame(Player $player): bool {
		return $this->getSpectatorManager()->isSpectator($player) || $this->getTeamManager()->hasTeam($player) || $this->isUnassociatedPlayer($player);
	}

	public function handleJoin(Player $player): void {
		if(count($this->getTeamManager()->getAll()) < 2) {
			[$id, $color] = $this->getTeamManager()->generateTeamData();
			$this->getTeamManager()->add(new Team(
				id: $id,
				color: $color,
				members: [$player]
			));
			$player->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create([
				"showCoordinates" => new BoolGameRule(true, true)
			]));
			$this->getScoreboardManager()->add($player);
		} else {
			$this->getSpectatorManager()->add($player);
			$this->getScoreboardManager()->add($player);
			$player->teleport($this->getArena()->getWorld()->getSpawnLocation());
			$player->setGamemode(GameMode::SPECTATOR());
		}
	}

	public function handleQuit(Player $player): void {}

	public function setTeamSpawnpoint(int $id, Vector3 $spawnpoint): void {
		$this->teamSpawnpoints[$id] = $spawnpoint;
	}

	public function getTeamSpawnpoint(int $id): Vector3 {
		return $this->teamSpawnpoints[$id];
	}

	public function getFirstTeam(): Team {
		return $this->getTeamManager()->get(1) ?? throw new AssumptionFailedError("No team found");
	}

	public function getSecondTeam(): Team {
		return $this->getTeamManager()->get(2) ?? throw new AssumptionFailedError("No team found");
	}

	public function handleEntityDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();
		if($entity instanceof Player) {
			$team = $this->getTeamManager()->getTeam($entity);
			if($team === null) {
				return;
			}
			if(!$event instanceof EntityDamageByChildEntityEvent) {
				$event->cancel();
				return;
			}

			$damager = $event->getDamager();
			assert($damager instanceof Player);
			$this->getArena()->getWorld()->addSound($damager->getPosition(), new NoteSound(NoteInstrument::PIANO(), 127), [$damager]);

			$event->cancel();
			$deathEvent = new PlayerDeathEvent($entity);
			$deathEvent->call();

			$this->kill($entity);
		} elseif($entity instanceof TeamFlagEntity && $event instanceof EntityDamageByEntityEvent) {
			$event->cancel();
			if($event instanceof EntityDamageByChildEntityEvent) {
				return;
			}
			$damager = $event->getDamager();
			assert($damager instanceof Player);
			$team = $entity->getTeam();
			if($this->getTeamManager()->getTeam($damager) !== $team) {
				$team->executeOnPlayers(function (Player $player): void {
					$this->kill($player);
				});
				$this->broadcastMessage(TextFormat::RED . $damager->getName() . " has destroyed " . $team->getFormattedName() . TextFormat::RED . "'s flag!");
			}
		}

	}

	public function kill(Player $player): void {
		if(!$this->getTeamManager()->hasTeam($player)) {
			return;
		}
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->setGamemode(GameMode::SPECTATOR());
		$this->getTeamManager()->setPlayerState($player, MemberState::DEAD());
	}

	/**
	 * @return array{armor: array<Item>, items: array<Item>}
	 */
	public function getKit(): array {
		$map = EnchantmentIdMap::getInstance();
		return [
			"armor" => [
				VanillaItems::LEATHER_CAP(),
				VanillaItems::LEATHER_TUNIC(),
				VanillaItems::LEATHER_PANTS(),
				VanillaItems::LEATHER_BOOTS()
			],
			"items" => [
				0 => CustomItems::CROSSBOW()->addEnchantment(new EnchantmentInstance($map->fromId(EnchantmentIds::QUICK_CHARGE), 3)),
				8 => VanillaItems::ARROW()->setCount(16)
			]
		];
	}

	public function broadcastMessage(string $message): void {
		$this->executeOnAll(function(Player $player) use ($message): void {
			$player->sendMessage($message);
		});
	}

	public function broadcastTip(string $tip): void {
		$this->executeOnAll(function(Player $player) use ($tip): void {
			$player->sendTip($tip);
		});
	}

	public function broadcastPopup(string $popup): void {
		$this->executeOnAll(function(Player $player) use ($popup): void {
			$player->sendPopup($popup);
		});
	}

	public function broadcastSound(Sound $sound) {
		$this->executeOnAll(function (Player $player) use($sound): void {
			$packets = $sound->encode($player->getPosition());
			foreach($packets as $packet) {
				$player->getNetworkSession()->sendDataPacket($packet);
			}
		});
	}
}