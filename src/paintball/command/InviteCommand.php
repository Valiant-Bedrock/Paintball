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

use libcommand\Overload;
use libcommand\parameter\types\TargetParameter;
use libcommand\PlayerCommand;
use libgame\GameBase;
use libgame\utilities\GameBaseTrait;
use paintball\form\InviteReceiveForm;
use paintball\game\custom\CustomPaintballGame;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InviteCommand extends PlayerCommand {
	use GameBaseTrait;

	public function __construct(GameBase $plugin) {
		parent::__construct("invite", "Invite a player to your paintball game", "/invite <player>");
		$this->registerOverload(new Overload("default", [new TargetParameter("player", false)]));
		$this->setPlugin($plugin);
	}

	public function onExecute(CommandSender $sender, array $arguments): bool|string {
		assert($sender instanceof Player);
		$game = $this->getPlugin()->getGameManager()->getGameByPlayer($sender);
		if(!$game instanceof CustomPaintballGame) {
			$sender->sendMessage(TextFormat::RED . "You must be in a custom game to use this command.");
			return true;
		}
		if($sender !== $game->getLeader()) {
			$sender->sendMessage(TextFormat::RED . "You must be the leader to use this command.");
			return true;
		}

		/** @var Player $player */
		$player = $arguments["player"];
		if(($playerGame = $game->getPlugin()->getGameManager()->getGameByPlayer($player)) !== null) {
			return TextFormat::RED . ($playerGame === $game ? "Player '{$player->getName()}' is already in your game!" : "Player '{$player->getName()}' is already in a game!");
		}
		$form = new InviteReceiveForm($game, $sender);
		$form->send($player);
		return TextFormat::GREEN . "Invite successfully sent to {$player->getName()}!";
	}
}