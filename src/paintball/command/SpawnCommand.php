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
use libgame\team\Team;
use paintball\entity\FlagEntity;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SpawnCommand extends PlayerCommand {

	public function __construct() {
		parent::__construct("spawn", "Spawns the flag entity", "/spawn", [], []);
		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	public function onExecute(CommandSender $sender, array $arguments): bool|string {
		assert($sender instanceof Player);

		$entity = new FlagEntity($sender->getLocation(), new Team(1, TextFormat::RED, []), null);
		$entity->spawnToAll();
		$entity->setNameTagAlwaysVisible();
		$entity->setNameTag("Flag");
		return TextFormat::GREEN . "Spawned flag entity";
	}
}