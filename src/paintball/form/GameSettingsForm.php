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

use Closure;
use libforms\CustomForm;
use libforms\elements\Dropdown;
use libforms\elements\Input;
use libforms\elements\Label;
use libforms\elements\Slider;
use libforms\elements\Toggle;
use libgame\team\TeamMode;
use paintball\game\custom\CustomPaintballGame;
use paintball\game\custom\CustomPaintballRoundManager;
use paintball\game\PaintballGame;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GameSettingsForm extends CustomForm {

	public function __construct(CustomPaintballGame $game) {
		/** @var CustomPaintballRoundManager $roundManager */
		$roundManager = $game->getRoundManager();
		$colorKeys = array_keys(PaintballGame::POSSIBLE_TEAM_COLORS);
		$colorValues = array_values(PaintballGame::POSSIBLE_TEAM_COLORS);
		parent::__construct(
			title: "Game Settings",
			elements: [
				new Label("Global Settings"),
				new Dropdown(
					text: "Team Mode",
					options: array_map(
						callback: fn(TeamMode $teamMode) => $teamMode->getFormattedName(),
						array: array_values(TeamMode::getAll())
					),
					default: array_search($game->getTeamMode(), array_values(TeamMode::getAll())) ?: 0,
					callable: function (string $formattedName) use($game): void {
						$newMode = TeamMode::fromString($formattedName);
						$game->setTeamMode($newMode);
					}
				),
				new Label("Team Settings"),
				new Input(text: "Team 1 Name", placeholder: "Team 1 Name", default: (string) $game->getFirstTeam(), callable: function(string $value) use($game): void {
					if($value !== "") {
						$game->getFirstTeam()->setCustomName($value);
					}
				}),
				new Dropdown(
					text: "Team 1 Color",
					options: $colorKeys,
					default: array_search(needle: $game->getFirstTeam()->getColor(), haystack: $colorValues) ?: 0,
					callable: function (string $value) use($game, ): void {
						$game->getFirstTeam()->setColor(PaintballGame::POSSIBLE_TEAM_COLORS[$value]);

					}
				),
				new Input(text: "Team 2 Name", placeholder: "Team 2 Name", default: (string) $game->getSecondTeam(), callable: function(string $value) use($game): void {
					if($value !== "") {
						$game->getSecondTeam()->setCustomName($value);
					}
				}),
				new Dropdown(
					text: "Team 2 Color",
					options: $colorKeys,
					default: array_search(needle: $game->getSecondTeam()->getColor(), haystack: $colorValues) ?: 0,
					callable: function (string $value) use($game, ): void {
						$game->getSecondTeam()->setColor(PaintballGame::POSSIBLE_TEAM_COLORS[$value]);
					}
				),
				new Label("Game Settings"),
				new Toggle(
					text: "Nametags Enabled",
					default: $game->isNameTagsEnabled(),
					callable: function (bool $value) use($game): void {
						$game->setNameTagsEnabled($value);
					}),
				new Slider(
					text: "Round Count",
					minimum: 1,
					maximum: 25,
					step: 1,
					default: $roundManager->getRoundCount(),
					callable: function(float|int $value) use($roundManager): void {
						$roundManager->setRoundCount((int) $value);
					}
				),
			],
			onClose: function (Player $player): void {
				$player->sendMessage(TextFormat::YELLOW . "Game settings have been updated.");
			}
		);
	}

}