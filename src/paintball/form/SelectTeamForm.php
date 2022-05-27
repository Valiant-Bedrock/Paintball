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
use paintball\game\PaintballGame;
use paintball\team\PaintballTeam;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;

class SelectTeamForm extends SimpleForm {

	public function __construct(PaintballGame $game) {
		parent::__construct(
			title: "Select Team",
			buttons: [
				...array_map(
					callback: fn(PaintballTeam $team) => new Button(
						text: $team . TextFormat::EOL . TextFormat::WHITE . "Players: " . TextFormat::YELLOW . count($team->getMembers()) . TextFormat::WHITE .  "/" . TextFormat::YELLOW . $game->getTeamMode()->getMaxPlayerCount(),
						onClick: function(Player $player) use($game, $team): void {
							/** @var PaintballGame $game */
							if($game->getSpectatorManager()->isSpectator($player)) {
								return;
							}
							$current = $game->getTeamManager()->getTeam($player);
							if($current === null || $current !== $team) {
								if(count($team->getMembers()) >= $game->getTeamMode()->getMaxPlayerCount()) {
									$player->sendMessage(TextFormat::RED . "You cannot join a full team!");
									return;
								}

								$game->removeUnassociatedPlayer($player);
								$current?->removeMember($player);
								$current?->broadcastMessage(TextFormat::YELLOW . $player->getName() . TextFormat::WHITE . " has left the team.");
								$team->addMember($player);
								$team->broadcastMessage(TextFormat::YELLOW . $player->getName() . TextFormat::WHITE . " has joined the team!");
							}
						}
					),
					array: [$game->getFirstTeam(), $game->getSecondTeam()]
				),
				new Button(
					text: TextFormat::YELLOW . "Remove From Team",
					onClick: function(Player $player) use($game): void {
						if ($game->getSpectatorManager()->isSpectator($player)) {
							return;
						}
						$current = $game->getTeamManager()->getTeam($player);
						if ($current !== null) {
							$current->removeMember($player);

							// Reset name tags back to default
							$player->setNameTagAlwaysVisible();
							$player->setNameTag($player->getName());

							$current->broadcastMessage(TextFormat::YELLOW . $player->getName() . TextFormat::WHITE . " has left the team.");
							$game->addUnassociatedPlayer($player);
						}
					}
				)
			]
		);
	}

}