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
use libforms\ModalForm;
use libgame\menu\HotbarMenu;
use libgame\menu\MenuEntry;
use paintball\form\SelectTeamForm;
use paintball\game\PaintballGame;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class PlayerHotbarMenu extends HotbarMenu {

	protected function __construct(PaintballGame $game) {
		parent::__construct([
			0 => new MenuEntry(
				item: VanillaBlocks::WOOL()->asItem()->setCustomName(TextFormat::LIGHT_PURPLE . "Select Team"),
				closure: function(Player $player) use($game): void {
					$form = new SelectTeamForm($game);
					$form->send($player);
				}
			),
			8 => new MenuEntry(
				item: VanillaBlocks::WOOL()->setColor(DyeColor::RED())->asItem()->setCustomName(TextFormat::RED . "Leave Game"),
				closure: function(Player $player) use($game): void {
					$form = new ModalForm(
						title: "Leave Game",
						content: "Are you sure you want to leave this game?",
						primaryButton: new Button(
							text: "Leave",
							onClick: function(Player $player) use($game): void { $game->handleQuit($player); }
						),
						secondaryButton: new Button(
							text: "Cancel",
							onClick: function(Player $player): void {}
						)
					);
					$form->send($player);
				}
			)
		]);
	}

	/**
	 * Given a game, this function will create a menu for the leader
	 *
	 * @param PaintballGame $game
	 * @return self
	 */
	public static function fromGame(PaintballGame $game): self {
		return new PlayerHotbarMenu($game);
	}

}