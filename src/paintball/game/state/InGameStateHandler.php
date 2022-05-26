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

namespace paintball\game\state;

use libgame\game\GameStateHandler;
use libgame\team\Team;
use paintball\arena\PaintballArena;
use paintball\game\PaintballGame;
use paintball\TeamFlagEntity;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class InGameStateHandler extends GameStateHandler {

	public function handleSetup(): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();
		/** @var PaintballArena $arena */
		$arena = $game->getArena();
		// Setup spawnpoints
		$availableSpawnpoints = [$arena->getFirstSpawnpoint(), $arena->getSecondSpawnpoint()];
		$game->executeOnTeams(function (Team $team) use ($game, &$availableSpawnpoints) {
			$spawnpointIndex = array_rand($availableSpawnpoints);
			/** @var Vector3 $spawnpoint */
			$spawnpoint = ($availableSpawnpoints[$spawnpointIndex])->add(0, 2, 0);
			$game->setTeamSpawnpoint($team->getId(), $spawnpoint);
			unset($availableSpawnpoints[$spawnpointIndex]);

			// Setup players
			$team->executeOnPlayers(function(Player $player) use($spawnpoint): void {
				$player->teleport(Position::fromObject($spawnpoint, $this->getGame()->getArena()->getWorld()));
				$player->setMaxHealth(PaintballGame::MAX_HEALTH);
				$player->setImmobile();
			});

			$world = $this->getGame()->getArena()->getWorld();
			$entity = new TeamFlagEntity(Location::fromObject($spawnpoint->add(0, 2, 0), $world), $team, null);
			$entity->setNameTag($team->getFormattedName() . "'s Flag");
			$entity->spawnToAll();
		});
	}

	public function handleTick(int $currentStateTime): void {
		/** @var PaintballGame $game - A bandaid solution to a larger problem */
		$game = $this->getGame();
		$round = $game->getRoundManager()->getCurrentRound();
		$game->getRoundManager()->handleTick();
		$game->executeOnAll(function (Player $player) use($round, $game): void {
			$scoreboard = $game->getScoreboardManager()->get($player);
			$firstScore = $game->getRoundManager()->getScore($game->getFirstTeam());
			$secondScore = $game->getRoundManager()->getScore($game->getSecondTeam());

			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::WHITE . "Round: " . TextFormat::YELLOW . $round->getRoundNumber() . TextFormat::WHITE . " | " . TextFormat::WHITE . "Time: " . TextFormat::YELLOW . $round->formatTime(),
				2 => "",
				3 => TextFormat::WHITE . "Team 1: " . TextFormat::YELLOW . $firstScore . TextFormat::WHITE . " | " . TextFormat::WHITE . "Team 2: " . TextFormat::YELLOW . $secondScore,
				4 => "------------------",
				5 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});
	}

	public function handleFinish(): void {

	}
}