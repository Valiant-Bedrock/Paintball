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
use paintball\game\PaintballGame;
use paintball\item\CustomItems;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;

class InGameStateHandler extends GameStateHandler {

	public function handleSetup(): void {
		$this->getGame()->executeOnAll(function(Player $player): void {
			$player->sendMessage(TextFormat::GREEN . "The game has started. Good luck!");
			$player->setImmobile(false);

			$player->getArmorInventory()->setContents([
				VanillaItems::LEATHER_CAP(),
				VanillaItems::LEATHER_TUNIC(),
				VanillaItems::LEATHER_PANTS(),
				VanillaItems::LEATHER_BOOTS()
			]);

			$player->getInventory()->setContents([
				0 => CustomItems::CROSSBOW(),
				8 => VanillaItems::ARROW()->setCount(1)
			]);
		});
	}

	public function handleTick(int $currentStateTime): void {
		/** @var PaintballGame $game - A bandaid solution to a larger problem */
		$game = $this->getGame();
		$round = $game->getCurrentRound();
		$game->executeOnAll(function (Player $player) use($round, $game): void {
			$scoreboard = $game->getScoreboardManager()->get($player);

			$scoreboard->setLines([
				0 => "------------------",
				1 => TextFormat::WHITE . "Round: " . TextFormat::YELLOW . $round->getRoundNumber() . TextFormat::WHITE . " | " . TextFormat::WHITE . "Time: " . TextFormat::YELLOW . $round->formatTime(),
				2 => "",
				3 => TextFormat::WHITE . "Team 1: " . TextFormat::YELLOW . "0" . TextFormat::WHITE . " | " . TextFormat::WHITE . "Team 2: " . TextFormat::YELLOW . "0",
				4 => "------------------",
				5 => TextFormat::YELLOW . "valiantnetwork.xyz",
			]);
		});
		$round->incrementTime();
	}

	public function handleFinish(): void {

	}
}