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
use libgame\game\RoundBasedGame;
use libgame\GameBase;
use libgame\kit\Kit;
use libgame\team\member\MemberState;
use libgame\team\Team;
use libgame\team\TeamMode;
use paintball\arena\PaintballArena;
use paintball\entity\FlagEntity;
use paintball\game\state\StartingStateHandler;
use paintball\game\state\InGameStateHandler;
use paintball\game\state\PostgameStateHandler;
use paintball\game\state\WaitingStateHandler;
use paintball\PaintballBase;
use paintball\PaintballEventHandler;
use paintball\utils\ArenaUtils;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;

class PaintballGame extends RoundBasedGame {

	public const TITLE = TextFormat::RED . TextFormat::BOLD . "VALIANT" . TextFormat::RESET . TextFormat::WHITE .  " - " . TextFormat::WHITE . "Paintball";
	public const HEARTBEAT_PERIOD = 20;

	public const TEAM_COUNT = 2;

	public const MAX_HEALTH = 1;

	protected PaintballEventHandler $eventHandler;

	/** @var array<int, Vector3> */
	protected array $teamSpawnpoints = [];

	public function __construct(GameBase $plugin, string $uniqueId, PaintballArena $arena, protected World $lobbyWorld, protected Kit $kit) {
		parent::__construct($plugin, $uniqueId, $arena, new PaintballRoundManager($this), TeamMode::SOLO(), self::TITLE,self::HEARTBEAT_PERIOD);
		$this->eventHandler = new PaintballEventHandler($this);
		$this->eventHandler->register($plugin);
	}

	public function getLobbyWorld(): World {
		return $this->lobbyWorld;
	}

	public function getKit(): Kit {
		return $this->kit;
	}

	public function start(): void {
		$this->setState(GameState::STARTING());
	}

	public function setupWaitingStateHandler(Game $game): GameStateHandler {
		return new WaitingStateHandler($game);
	}

	public function setupStartingStateHandler(Game $game): GameStateHandler {
		return new StartingStateHandler($game);
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
		if(count($this->getTeamManager()->getAll()) < self::TEAM_COUNT) {
			[$id, $color] = $this->getTeamManager()->generateTeamData();
			$this->getTeamManager()->add(new Team(
				id: $id,
				color: $color,
				members: [$player]
			));
			$player->setNameTag($color . $player->getName());
			$player->teleport($this->getLobbyWorld()->getSpawnLocation());
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
		$player->sendMessage(TextFormat::YELLOW . "You have joined the match!");
		$this->broadcastMessage(TextFormat::YELLOW . "{$player->getName()} has joined the match!");
	}

	public function handleQuit(Player $player): void {
		$this->getScoreboardManager()->remove($player);
		if($this->getSpectatorManager()->isSpectator($player)) {
			$this->getSpectatorManager()->remove($player);
		}
		if(($team = $this->getTeamManager()->getTeam($player)) !== null) {
			$team->removeMember($player);
			if(count($team->getMembers()) === 0) {
				$this->getTeamManager()->remove($team);
			}

			$teams = $this->getTeamManager()->getAll();
			if(count($teams) === 1) {
				$remainingTeam = $teams[array_key_first($teams)];
				$this->broadcastMessage(TextFormat::YELLOW . "All members of $team have left the match! $remainingTeam wins!");
				$this->setState(GameState::POSTGAME());
			}
		}
		$player->sendMessage(TextFormat::YELLOW . "You have left the match!");
		$this->broadcastMessage(TextFormat::YELLOW . "{$player->getName()} has left the match");
	}

	public function setTeamSpawnpoint(Team $team, Vector3 $spawnpoint): void {
		$this->teamSpawnpoints[$team->getId()] = $spawnpoint;
	}

	public function getTeamSpawnpoint(Team $team): Vector3 {
		return $this->teamSpawnpoints[$team->getId()];
	}

	public function getFirstTeam(): Team {
		return $this->getTeamManager()->get(1) ?? throw new AssumptionFailedError("No team found");
	}

	public function getSecondTeam(): Team {
		return $this->getTeamManager()->get(2) ?? throw new AssumptionFailedError("No team found");
	}

	/**
	 * This method configures a team
	 *
	 * @param Team $team
	 * @return void
	 */
	public function setupTeam(Team $team): void {
		$spawnpoint = $this->getTeamSpawnpoint($team);
		// Setup players
		$team->executeOnPlayers(function(Player $player) use($spawnpoint): void {
			// Ensure player is alive
			$this->getTeamManager()->setPlayerState($player, MemberState::ALIVE());
			$player->setMaxHealth(PaintballGame::MAX_HEALTH);
			$player->setImmobile();
			$this->setupPlayer($player);
			$player->teleport(Position::fromObject($spawnpoint, $this->getArena()->getWorld()));
		});

		$world = $this->getArena()->getWorld();
		$entity = new FlagEntity(Location::fromObject($spawnpoint->add(0, 2, 0), $world), $team, null);
		$entity->setNameTag(TextFormat::YELLOW . "$team's Flag");
		$entity->spawnToAll();
	}

	public function setupPlayer(Player $player): void {
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->setGamemode(GameMode::ADVENTURE());
		$player->setHealth($player->getMaxHealth());
		$player->setNameTagAlwaysVisible();
	}

	public function kill(Player $player): void {
		if(!$this->getTeamManager()->hasTeam($player)) {
			return;
		}
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->setGamemode(GameMode::SPECTATOR());
		$this->getTeamManager()->setPlayerState($player, MemberState::DEAD());
	}

	public function finish(): void {
		$this->getHeartbeat()->cancel();
		$this->getPlugin()->getGameManager()->remove($this);
		$this->eventHandler->unregister();

		$this->executeOnAll(function(Player $player): void {
			// Reset nametag
			$player->setNameTag($player->getName());
			$player->setNameTagAlwaysVisible(false);
			$player->setGamemode(GameMode::ADVENTURE());
			// Disable flight
			$player->setAllowFlight(false);
			$player->setFlying(false);
			// Set health
			$player->setMaxHealth(20);
			$player->setHealth($player->getMaxHealth());

			// Clear inventories
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->getCursorInventory()->clearAll();

			$player->teleport($this->getPlugin()->getServer()->getWorldManager()->getDefaultWorld()?->getSafeSpawn() ?? throw new AssumptionFailedError("No default world"));
			if($this->getSpectatorManager()->isSpectator($player)) {
				$this->getSpectatorManager()->remove($player);
			}
			if($this->isUnassociatedPlayer($player)) {
				$this->removeUnassociatedPlayer($player);
			}
			$scoreboard = $this->getScoreboardManager()->get($player);
			$scoreboard->remove();
			$this->getScoreboardManager()->remove($player);
		});

		$this->executeOnTeams(function(Team $team): void { $this->getTeamManager()->remove($team); });
		/** @var PaintballArena $arena */
		$arena = $this->getArena();
		if($arena->isGenerated()) {
			$this->getServer()->getWorldManager()->unloadWorld($arena->getWorld());
			ArenaUtils::deleteDirectory($this->getServer()->getDataPath() . "worlds/{$arena->getWorld()->getFolderName()}");
		} else {
			PaintballBase::getInstance()->getArenaManager()->setOccupied($arena, false);
		}
		$this->getServer()->getWorldManager()->unloadWorld($this->getLobbyWorld());
		ArenaUtils::deleteDirectory($this->getServer()->getDataPath() . "worlds/{$this->getLobbyWorld()->getFolderName()}");
	}
}