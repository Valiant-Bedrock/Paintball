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

namespace paintball\game\custom;

use libforms\buttons\Button;
use libforms\CustomForm;
use libforms\elements\Dropdown;
use libforms\elements\Input;
use libforms\elements\Label;
use libforms\elements\Slider;
use libforms\elements\Toggle;
use libforms\ModalForm;
use libgame\menu\HotbarMenu;
use libgame\menu\MenuEntry;
use libgame\team\TeamMode;
use libgame\utilities\Utilities;
use paintball\form\GameSettingsForm;
use paintball\form\SelectTeamForm;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class LeaderHotbarMenu extends HotbarMenu {

	protected function __construct(CustomPaintballGame $game) {
		parent::__construct(array_filter([
			0 => new MenuEntry(
				item: VanillaItems::NETHER_STAR()->setCustomName(TextFormat::LIGHT_PURPLE . "Start"),
				closure: function(Player $player) use($game): void {
					foreach($game->getTeamManager()->getAll() as $team) {
						if(count($team->getMembers()) === 0) {
							$player->sendMessage(TextFormat::RED . "You must have at least one player on each team to force start.");
							return;
						}
					}

					$form = new ModalForm(
						title: "Start",
						content: "Are you sure you want to start the game?",
						primaryButton: new Button(
							text: "Confirm",
							onClick: function(Player $player) use($game): void {
								$game->start();
								$game->broadcastMessage(TextFormat::YELLOW . "Game has been started by the host.");
							}
						),
						secondaryButton: new Button(text: "Cancel", onClick: function(Player $player) : void {})
					);
					$form->send($player);
				}
			),
			1 => new MenuEntry(
				item: VanillaBlocks::WOOL()->asItem()->setCustomName(TextFormat::LIGHT_PURPLE . "Select Team"),
				closure: function(Player $player) use($game): void {
					if($game->getSpectatorManager()->isSpectator($player)) {
						$player->sendMessage(TextFormat::RED . "You must be a player to select a team.");
						return;
					}
					$form = new SelectTeamForm($game);
					$form->send($player);
				}
			),
			7 => new MenuEntry(
				item: VanillaItems::BOOK()->setCustomName(TextFormat::LIGHT_PURPLE . "Game Settings"),
				closure: function(Player $player) use($game,): void {
					$form = new GameSettingsForm($game);
					$form->send($player);
				}
			),
			8 => new MenuEntry(
				item: VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem()->setCustomName(TextFormat::RED . "Delete Game"),
				closure: function(Player $player) use($game): void {
					$form = new ModalForm(
						title: "Delete Game",
						content: "Are you sure you want to delete this game?",
						primaryButton: new Button(
							text: "Delete",
							onClick: function(Player $player) use($game): void {
								$game->broadcastMessage(TextFormat::RED . "Game deleted.");
								$game->delete();
							}
						),
						secondaryButton: new Button(
							text: "Cancel",
							onClick: function(Player $player): void {}
						)
					);
					$form->send($player);
				}
			)
		]));
	}

	/**
	 * Given a game, this function will create a menu for the leader
	 *
	 * @param CustomPaintballGame $game
	 * @return self
	 */
	public static function fromGame(CustomPaintballGame $game): self {
		return new LeaderHotbarMenu($game);
	}

}