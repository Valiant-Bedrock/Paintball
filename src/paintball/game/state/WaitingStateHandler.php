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

use libgame\game\GameState;
use libgame\game\GameStateHandler;
use paintball\game\PaintballGame;
use paintball\PaintballBase;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class WaitingStateHandler extends GameStateHandler {

	public function handleSetup(): void {
		foreach($this->game->getArena()->getWorld()->getEntities() as $entity) {
			if(!$entity instanceof Player) {
				$entity->close();
			}
		}
	}

	public function handleTick(int $currentStateTime): void {
		/** @var PaintballGame $game */
		$game = $this->getGame();
		if(count($game->getTeamManager()->getAll()) === 2) {
			$game->broadcastMessage(TextFormat::GREEN . "Team limit reached! Starting game...");
			$game->setState(GameState::STARTING());
			return;
		}

		$game->executeOnAll(function (Player $player) use($game): void {
			$scoreboard = $game->getScoreboardManager()->get($player);
			$scoreboard->setLines(PaintballBase::formatScoreboard([
				TextFormat::GREEN . "Waiting for players..."
			]));
		});
	}

	public function handleFinish(): void {}
}