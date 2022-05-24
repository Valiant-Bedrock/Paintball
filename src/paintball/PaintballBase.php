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

use libgame\GameBase;
use paintball\game\PaintballGame;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

// Import dependencies
require_once dirname(__DIR__, 2) . "/vendor/autoload.php";

class PaintballBase extends GameBase {

	protected ?PaintballGame $defaultGame = null;

	protected function onEnable() : void {
		$this->getServer()->getWorldManager()->loadWorld("paintball_test", true);
		$this->getServer()->getPluginManager()->registerEvents(
			listener: new PaintballListener($this),
			plugin: $this
		);
	}

	protected function onDisable() : void {

	}

	public function getDefaultGame(): ?PaintballGame {
		return $this->defaultGame;
	}

	public function setDefaultGame(?PaintballGame $game): void {
		$this->defaultGame = $game;
	}

	public function hasDefaultGame(): bool {
		return $this->defaultGame instanceof PaintballGame;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		switch(strtolower($label)) {
			case "start":
				if(!$sender instanceof Player) {
					$sender->sendMessage("You must be a player to use this command.");
					return true;
				}
				if(!$this->hasDefaultGame()) {
					$sender->sendMessage("There is no default game set.");
					return true;
				}

				$this->getDefaultGame()->start();
				return true;
		}
		return true;
	}
}