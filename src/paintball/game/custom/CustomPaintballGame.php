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

namespace paintball\game\custom;

use libgame\game\Game;
use libgame\game\GameState;
use libgame\game\GameStateHandler;
use libgame\GameBase;
use libgame\kit\Kit;
use libgame\team\member\MemberState;
use libgame\team\Team;
use libgame\team\TeamMode;
use paintball\arena\PaintballArena;
use paintball\game\PaintballGame;
use paintball\game\PaintballRoundManager;
use paintball\team\PaintballTeam;
use paintball\team\PaintballTeamManager;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class CustomPaintballGame extends PaintballGame {


	protected bool $nameTagsEnabled = true;

	protected bool $respawns = false;

	protected PlayerHotbarMenu $playerHotbarMenu;
	protected LeaderHotbarMenu $leaderHotbarMenu;

	public function __construct(
		GameBase $plugin,
		string $uniqueId,
		PaintballArena $arena,
		TeamMode $teamMode,
		protected World $lobbyWorld,
		protected Kit $kit,
		protected Player $leader
	) {
		parent::__construct($plugin, $uniqueId, $arena, $teamMode, $lobbyWorld, $kit);
		$this->roundManager = new CustomPaintballRoundManager($this);

		$this->playerHotbarMenu = PlayerHotbarMenu::fromGame($this);
		$this->leaderHotbarMenu = LeaderHotbarMenu::fromGame($this);

		// Team 1
		[$id, $color] = $this->getTeamManager()->generateTeamData();
		$this->getTeamManager()->add(new PaintballTeam(id: $id, color: $color, members: []));
		// Team 2
		[$id, $color] = $this->getTeamManager()->generateTeamData();
		$this->getTeamManager()->add(new PaintballTeam(id: $id, color: $color, members: []));
	}

	public function getLeader(): Player {
		return $this->leader;
	}

	public function setLeader(Player $leader): void {
		$this->leader = $leader;
	}

	public function setTeamMode(TeamMode $teamMode): void {
		$this->teamMode = $teamMode;
	}

	public function getPlayerHotbarMenu(): PlayerHotbarMenu {
		return $this->playerHotbarMenu;
	}

	public function getLeaderHotbarMenu(): LeaderHotbarMenu {
		return $this->leaderHotbarMenu;
	}

	public function setupWaitingStateHandler(Game $game): GameStateHandler {
		return new CustomWaitingStateHandler($game);
	}

	public function start(): void {
		/** @var PaintballTeamManager $teamManager */
		$teamManager = $this->getTeamManager();
		$teamManager->setupTeamStates();
		parent::start();
	}

	public function handleJoin(Player $player): void {
		// Clear inventories
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getHungerManager()->setEnabled(false);

		if($this->getState()->equals(GameState::WAITING())) {
			$this->addUnassociatedPlayer($player);
			$player->setGamemode(GameMode::ADVENTURE());
			$player->teleport($this->getLobbyWorld()->getSpawnLocation());

			$player->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create(["showCoordinates" => new BoolGameRule(true, true)]));
		} else {
			$this->getSpectatorManager()->add($player);
			$player->teleport($this->getArena()->getWorld()->getSpawnLocation());
			$player->setGamemode(GameMode::SPECTATOR());
			// Ensure block collision is on
			$player->setHasBlockCollision(true);
		}
		$this->getScoreboardManager()->add($player);
		$player->sendMessage(TextFormat::YELLOW . "You have joined the match!");
		$this->broadcastMessage(TextFormat::YELLOW . "{$player->getName()} has joined the match!");
		$hotbarMenu = $player === $this->leader ? $this->getLeaderHotbarMenu() : $this->getPlayerHotbarMenu();
		$hotbarMenu->send($player);
	}

	public function setNametagData(Player $player, Team $team): void {
		$player->sendData(
			targets: $team->getOnlineMembers(),
			data: [
				EntityMetadataProperties::NAMETAG => new StringMetadataProperty(TextFormat::GREEN . $player->getName()),
				EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => new ByteMetadataProperty(1),
			]
		);
		$oppositeTeam = $team === $this->getFirstTeam() ? $this->getSecondTeam() : $this->getFirstTeam();
		$player->sendData(
			targets: $oppositeTeam->getOnlineMembers(),
			data: [
				EntityMetadataProperties::NAMETAG => new StringMetadataProperty(TextFormat::RED . $player->getName()),
				EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => new ByteMetadataProperty((!$this->getState()->equals(GameState::IN_GAME()) || $this->isNameTagsEnabled()) ? 1 : 0),
			]
		);
	}

	public function isNameTagsEnabled(): bool {
		return $this->nameTagsEnabled;
	}

	public function setNameTagsEnabled(bool $nameTagsEnabled): void {
		$this->nameTagsEnabled = $nameTagsEnabled;
	}

	public function allowsRespawns(): bool {
		return $this->respawns;
	}

	public function setAllowRespawns(bool $respawns): void {
		$this->respawns = $respawns;
	}

	public function finish(): void {
		$this->setState(GameState::WAITING());

		$this->roundManager = new PaintballRoundManager($this);

		$this->executeOnAll(function(Player $player): void {
			// Reset name tag
			$player->setNameTag($player->getName());
			$player->setNameTagVisible();

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

			// Disable coordinates
			$player->getNetworkSession()->sendDataPacket(GameRulesChangedPacket::create(["showCoordinates" => new BoolGameRule(false, true)]));

			$player->teleport($this->getLobbyWorld()->getSpawnLocation());

			$hotbarMenu = $player === $this->leader ? $this->getLeaderHotbarMenu() : $this->getPlayerHotbarMenu();
			$hotbarMenu->send($player);
		});
	}

	/**
	 * Deletes the custom game
	 *
	 * @return void
	 */
	public function delete(): void {
		parent::finish();
	}

}