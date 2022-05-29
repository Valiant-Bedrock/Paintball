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
use libcommand\parameter\types\StringParameter;
use libcommand\PlayerCommand;
use libgame\GameBase;
use libgame\utilities\GameBaseTrait;
use paintball\arena\PaintballArenaCreator;
use paintball\PaintballBase;
use paintball\PaintballPermissions;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;

class CreateArenaCommand extends PlayerCommand {
	use GameBaseTrait;

	public function __construct(GameBase $plugin) {
		parent::__construct("create-arena", "Creates a paintball arena", "/create-arena [world]");
		$this->registerOverload(new Overload(
			name: "default",
			parameters: [new StringParameter(name: "world", optional: true)]
		));
		$this->setPermission((string) PaintballPermissions::CREATE_ARENA());
		$this->setPlugin($plugin);
	}

	public function onExecute(CommandSender $sender, array $arguments): bool|string {
		assert($sender instanceof Player);
		$worldName = $args["world"] ?? $sender->getWorld()->getFolderName();
		$loaded = $sender->getServer()->getWorldManager()->loadWorld($worldName);
		if(!$loaded) {
			return TextFormat::RED . "Unable to locate world: '$worldName'";
		}
		/** @var PaintballBase $plugin */
		$plugin = $this->getPlugin();
		$world = $sender->getServer()->getWorldManager()->getWorldByName($worldName) ?? throw new AssumptionFailedError("World $worldName does not exist.");
		$sender->teleport($world->getSpawnLocation());
		$creator = new PaintballArenaCreator(creator: $sender, plugin: $plugin);
		$creator->start();
		return TextFormat::GREEN . "Starting arena creation process...";
	}
}