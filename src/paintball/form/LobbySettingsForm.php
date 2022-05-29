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

use libforms\buttons\Button;
use libforms\SimpleForm;
use libgame\game\Game;
use libgame\game\GameState;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class LobbySettingsForm extends SimpleForm {

	public function __construct(Game $game) {
		parent::__construct(
			title: "Lobby Settings",
			buttons: array_filter([
				// Only send this button if the game has started
				!$game->getState()->equals(GameState::WAITING()) ? new Button(
					text: "Return to Lobby",
					onClick: function(Player $player) use($game): void {
						$game->finish();
						$game->broadcastMessage(TextFormat::YELLOW . "Game has been cancelled by the host.");
					}
				) : null,
				new Button(
					text: "Invite Player",
					onClick: function(Player $player) use($game): void {
						$form = new InviteSendForm($player, $game);
						$form->send($player);
					}
				)
			])
		);
	}

}