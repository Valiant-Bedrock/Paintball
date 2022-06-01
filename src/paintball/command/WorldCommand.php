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

use libcommand\Command;
use libcommand\Overload;
use libcommand\parameter\types\StringParameter;
use libcommand\parameter\types\SubcommandParameter;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\world\generator\Flat;
use pocketmine\world\WorldCreationOptions;

class WorldCommand extends Command {

	public function __construct() {
		parent::__construct("world", "World commands", "/world <generate|load|teleport>");
		$this->registerOverload(new Overload("generate", [
			new SubcommandParameter("generate"),
			new StringParameter("worldName")
		]));
		$this->registerOverload(new Overload("default", [
			new SubcommandParameter("load"),
			new StringParameter("worldName")
		]));
		$this->registerOverload(new Overload("teleport", [
			new SubcommandParameter("teleport"),
			new StringParameter("worldName")
		]));
	}

	public function onExecute(CommandSender $sender, array $arguments): bool|string {
		/** @var string $name */
		$name = $arguments["worldName"];

		$worldManager = $sender->getServer()->getWorldManager();
		if($worldManager->loadWorld($name)) {
			return TextFormat::RED . "World already exists";
		}
		$generated = $worldManager->generateWorld($name, WorldCreationOptions::create()->setGeneratorClass(Flat::class));
		if(!$generated) {
			return TextFormat::RED . "Failed to generate world";
		}
		return TextFormat::GREEN . "World created successfully!";
	}
}