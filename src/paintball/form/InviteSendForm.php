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

namespace paintball\form;

use libforms\CustomForm;
use libforms\elements\Input;
use libforms\elements\Label;
use libgame\game\Game;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InviteSendForm extends CustomForm {

	public function __construct(Player $leader, Game $game) {
		parent::__construct(
			title: "Invite Player",
			elements: [
				new Label(text: "Please enter the player's name:"),
				new Input(text: "Player name", placeholder: "Player name", default: "", callable: function (string $name) use($leader, $game): void {
					$player = $game->getServer()->getPlayerExact($name);
					if($player === null) {
						$leader->sendMessage(TextFormat::RED . "Player '$name' not found!");
						return;
					}
					$playerGame = $game->getPlugin()->getGameManager()->getGameByPlayer($player);
					if($playerGame !== null) {
						$leader->sendMessage(TextFormat::RED . ($playerGame === $game ? "Player '{$player->getName()}' is already in your game!" : "Player '{$player->getName()}' is already in a game!"));
						return;
					}
					$form = new InviteReceiveForm($game, $leader);
					$form->send($player);
				})
			]
		);
	}

}