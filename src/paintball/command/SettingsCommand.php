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

namespace paintball\command;

use libcommand\PlayerCommand;
use libgame\GameBase;
use libgame\utilities\GameBaseTrait;
use paintball\form\LobbySettingsForm;
use paintball\game\custom\CustomPaintballGame;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SettingsCommand extends PlayerCommand {
	use GameBaseTrait;

	public function __construct(GameBase $plugin) {
		parent::__construct("settings", "Adjust custom game settings", "/settings", []);
		$this->setPlugin($plugin);
	}

	public function onExecute(CommandSender $sender, array $arguments): bool|string {
		assert($sender instanceof Player);
		$game = $this->getPlugin()->getGameManager()->getGameByPlayer($sender);
		if(!$game instanceof CustomPaintballGame) {
			$sender->sendMessage(TextFormat::RED . "You must be in a custom game to use this command.");
			return true;
		}
		if($game->getLeader() !== $sender) {
			$sender->sendMessage(TextFormat::RED . "You must be the leader to use this command.");
			return true;
		}
		$form = new LobbySettingsForm($game);
		$form->send($sender);
		return true;
	}
}