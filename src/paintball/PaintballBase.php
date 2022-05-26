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

use Closure;
use libforms\buttons\Button;
use libforms\SimpleForm;
use libgame\arena\Arena;
use libgame\arena\ArenaManager;
use libgame\game\GameState;
use libgame\GameBase;
use paintball\arena\PaintballArena;
use paintball\arena\PaintballArenaCreator;
use paintball\arena\PaintballArenaManager;
use paintball\game\PaintballGame;
use paintball\item\PaintballKits;
use paintball\utils\ArenaUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

// Import dependencies
require_once dirname(__DIR__, 2) . "/vendor/autoload.php";

class PaintballBase extends GameBase {

	public const LOBBIES_WORLD_PATH = "lobbies/";
	public const GENERATED_WORLD_PATH = "generated/";

	protected static PaintballBase $instance;

	protected ?PaintballGame $defaultGame = null;

	protected function onLoad(): void {
		self::$instance = $this;

		$this->getServer()->getWorldManager()->loadWorld("lobby", true);
		parent::onLoad();
	}

	protected function onEnable() : void {
		$this->getArenaManager()->load();

		@mkdir($this->getServer()->getDataPath() . "worlds/" . self::GENERATED_WORLD_PATH);
		@mkdir($this->getServer()->getDataPath() . "worlds/" . self::LOBBIES_WORLD_PATH);

		$this->getServer()->getPluginManager()->registerEvents(
			listener: new PaintballListener($this),
			plugin: $this
		);
	}

	protected function onDisable() : void {
		ArenaUtils::deleteDirectory($this->getServer()->getDataPath() . "worlds/" . self::GENERATED_WORLD_PATH);
	}

	public static function getInstance(): PaintballBase {
		return self::$instance;
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
				$arena = $this->getArenaManager()->findOpenArena();
				if($arena === null) {
					$sender->sendMessage(TextFormat::YELLOW . "There are no open arenas at the moment. Attempting to generate one...");
					$this->generateArena(
						onSuccess: function(Arena $arena) use($sender): void {
							$game = new PaintballGame(plugin: $this, uniqueId: uniqid(), arena: $arena, lobbyWorld: $this->createLobby(), kit: PaintballKits::VANILLA());
							$this->getGameManager()->add($game);
							$this->setDefaultGame($game);
							$game->handleJoin($sender);
						},
						onFailure: function(string $errorMessage) use($sender): void {
							$sender->sendMessage(TextFormat::RED . "Failed to generate an arena: $errorMessage");
						}
					);
					return true;
				}
				$this->getArenaManager()->setOccupied($arena, true);
				$game = new PaintballGame(plugin: $this, uniqueId: uniqid(), arena: $arena, lobbyWorld: $this->createLobby(), kit: PaintballKits::VANILLA());
				$this->getGameManager()->add($game);
				$this->setDefaultGame($game);
			case "join":
				if(!$sender instanceof Player) {
					$sender->sendMessage("You must be a player to use this command.");
					return true;
				}
				if($this->getGameManager()->getGameByPlayer($sender) !== null) {
					$sender->sendMessage(TextFormat::RED . "You can't use this command while in a game");
					return true;
				}

				$form = new SimpleForm(
					title: "Game Selector",
					buttons: array_map(
						callback: fn(PaintballGame $game) => new Button(
							text: TextFormat::YELLOW . "Game ({$game->getKit()->getName()})" . TextFormat::WHITE . "[" . $this->getNameFromState($game->getState()) . TextFormat::WHITE . "]",
							onClick: function (Player $player) use($game): void {
								$game->handleJoin($player);
							}),
						array: array_values($this->getGameManager()->getAll())
					)
				);
				$form->send($sender);
				return true;
		}
		return true;
	}

	public function getNameFromState(GameState $state): string {
		return match($state->id()) {
			GameState::WAITING()->id() => TextFormat::GREEN . "Waiting",
			GameState::STARTING()->id() => TextFormat::YELLOW . "Starting",
			GameState::IN_GAME()->id() => TextFormat::YELLOW . "In Game",
			GameState::POSTGAME()->id() => TextFormat::YELLOW . "Postgame",
			default => TextFormat::RED . "Unknown"
		};
	}

	/**
	 * @param Closure(Arena): void $onSuccess
	 * @param Closure(string): void $onFailure
	 * @return void
	 */
	protected function generateArena(Closure $onSuccess, Closure $onFailure): void {
		// Pick template
		$templates = ArenaUtils::getTemplates();
		$template = $templates[array_rand($templates)];
		// Create folder name with unique ID
		$folderName =  self::GENERATED_WORLD_PATH . uniqid($template . "-");
		// Copy contents to world
		if(!ArenaUtils::copyDirectory($this->getDataFolder() . "arena_templates/" . $template, $this->getServer()->getDataPath() . "worlds/" . $folderName)) {
			$onFailure("Failed to copy directory.");
			return;
		}
		// Load world & configure
		$loaded = $this->getServer()->getWorldManager()->loadWorld($folderName);
		if(!$loaded) {
			$onFailure("Failed to load world.");
			return;
		}
		$world = $this->getServer()->getWorldManager()->getWorldByName($folderName) ?? throw new AssumptionFailedError("World $folderName does not exist.");
		$world->setAutoSave(false);
		// Get arena spawnpoints
		/** @var PaintballArenaManager $arenaManager */
		$arenaManager = $this->getArenaManager();
		$data = $arenaManager->getArenaDataByWorldName($template) ?? throw new AssumptionFailedError("Arena data for $template does not exist.");
		$arena = new PaintballArena(world: $world, firstSpawnpoint: $data->getFirstSpawnpoint(), secondSpawnpoint: $data->getSecondSpawnpoint(), generated: true);
		$onSuccess($arena);
	}

	protected function setupArenaManager(): ArenaManager {
		return new PaintballArenaManager($this);
	}

	public function createLobby(): World {
		$lobbyPath = self::LOBBIES_WORLD_PATH . uniqid("lobby-");
		ArenaUtils::copyDirectory($this->getDataFolder() . "lobby", $this->getServer()->getDataPath() . "worlds/$lobbyPath");
		$this->getServer()->getWorldManager()->loadWorld($lobbyPath);
		return $this->getServer()->getWorldManager()->getWorldByName($lobbyPath) ?? throw new AssumptionFailedError("World $lobbyPath does not exist.");
	}
}