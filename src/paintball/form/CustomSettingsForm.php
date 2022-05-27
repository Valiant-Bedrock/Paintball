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
use libgame\game\GameState;
use paintball\game\custom\CustomPaintballGame;
use pocketmine\utils\TextFormat;

class CustomSettingsForm extends SimpleForm {

	public function __construct(CustomPaintballGame $game) {
		parent::__construct(
			title: "Custom Settings",
			buttons: [
				$game->getState()->equals(GameState::WAITING()) ?
					new Button(text: TextFormat::YELLOW . "Start Game", onClick: function() use($game): void { $game->start(); }) :
					new Button(text: TextFormat::YELLOW . "Return to Lobby", onClick: function () use($game): void { $game->finish(); })
			]
		);
	}

}