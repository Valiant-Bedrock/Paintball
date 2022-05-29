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
use libforms\ModalForm;
use libgame\game\Game;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class InviteReceiveForm extends ModalForm {

	public function __construct(Game $game, Player $inviter) {
		parent::__construct(
			title: TextFormat::YELLOW . "Game Invite",
			content: TextFormat::YELLOW . "You have been invited to join " . TextFormat::GREEN . $inviter->getName() . TextFormat::YELLOW . "'s custom game. Do you wish to accept?",
			primaryButton: new Button(TextFormat::GREEN . "Accept", function (Player $player) use($game, $inviter): void {
				$inviter->sendMessage(TextFormat::GREEN . "{$player->getName()} has accepted your invite.");
				$player->sendMessage(TextFormat::GREEN . "You have accepted the invite.");
				$game->handleJoin($player);
			}),
			secondaryButton: new Button(TextFormat::RED . "Decline", function (Player $player) use($game, $inviter): void {
				$inviter->sendMessage(TextFormat::RED . "{$player->getName()} has declined your invite.");
				$player->sendMessage(TextFormat::RED . "You have declined the invite.");
			})
		);
	}

}