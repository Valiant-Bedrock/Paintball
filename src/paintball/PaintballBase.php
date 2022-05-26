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

namespace paintball;

use libgame\Arena;
use libgame\game\GameManager;
use libgame\GameBase;
use paintball\arena\PaintballArenaCreator;
use paintball\arena\PaintballArenaManager;
use paintball\game\PaintballGame;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\EntityDataHelper as Helper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

// Import dependencies
require_once dirname(__DIR__, 2) . "/vendor/autoload.php";

class PaintballBase extends GameBase {

	protected static PaintballBase $instance;

	protected PaintballArenaManager $arenaManager;

	protected ?PaintballGame $defaultGame = null;

	protected function onLoad(): void {
		self::$instance = $this;
	}

	protected function onEnable() : void {
		$this->arenaManager = new PaintballArenaManager($this);
		$this->arenaManager->load();

		$this->gameManager = new GameManager($this);

		$this->getServer()->getPluginManager()->registerEvents(
			listener: new PaintballListener($this),
			plugin: $this
		);
	}

	protected function onDisable() : void {
		$this->arenaManager->save();
	}

	public static function getInstance(): PaintballBase {
		return self::$instance;
	}

	public function getArenaManager(): PaintballArenaManager {
		return $this->arenaManager;
	}

	public function getDefaultGame(): ?PaintballGame {
		return $this->defaultGame;
	}

	public function setDefaultGame(?PaintballGame $game): void {
		$this->defaultGame = $game;
	}

	public function hasDefaultGame(): bool {
		return $this->defaultGame instanceof PaintballGame;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
		switch(strtolower($label)) {
			case "start":
				if(!$sender instanceof Player) {
					$sender->sendMessage("You must be a player to use this command.");
					return true;
				}
				if(!$this->hasDefaultGame()) {
					$sender->sendMessage("There is no default game set.");
					return true;
				}

				$this->getDefaultGame()?->start();
				return true;
			case "createarena":
				if(!$sender instanceof Player) {
					$sender->sendMessage("You must be a player to use this command.");
					return true;
				}
				$worldName = $args[0] ?? $sender->getWorld()->getFolderName();
				$this->getServer()->getWorldManager()->loadWorld($worldName);
				$world = $this->getServer()->getWorldManager()->getWorldByName($worldName) ?? throw new AssumptionFailedError("World $worldName does not exist.");
				$sender->teleport($world->getSpawnLocation());
				$creator = new PaintballArenaCreator(creator: $sender, plugin: $this);
				$creator->start();
				return true;
			case "creategame":
				if(!$sender instanceof Player) {
					$sender->sendMessage("You must be a player to use this command.");
					return true;
				}
		}
		return true;
	}
}