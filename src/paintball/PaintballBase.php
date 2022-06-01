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
use libcommand\Command;
use libcommand\LibCommandBase;
use libcommand\Overload;
use libcommand\parameter\types\BoolParameter;
use libcommand\parameter\types\CommandParameter;
use libcommand\parameter\types\EquipmentSlotParameter;
use libcommand\parameter\types\FilepathParameter;
use libforms\buttons\Button;
use libforms\CustomForm;
use libforms\elements\Input;
use libforms\elements\Label;
use libforms\SimpleForm;
use libgame\arena\Arena;
use libgame\arena\ArenaManager;
use libgame\game\Game;
use libgame\game\GameState;
use libgame\GameBase;
use libgame\menu\HotbarMenu;
use libgame\menu\MenuEntry;
use libgame\team\TeamMode;
use libgame\utilities\DeployableClosure;
use libscoreboard\Scoreboard;
use paintball\arena\PaintballArena;
use paintball\arena\PaintballArenaManager;
use paintball\command\CreateArenaCommand;
use paintball\command\InviteCommand;
use paintball\command\BlockCommand;
use paintball\command\LogoCommand;
use paintball\command\SettingsCommand;
use paintball\command\SpawnCommand;
use paintball\command\WorldCommand;
use paintball\game\custom\CustomPaintballGame;
use paintball\game\PaintballGame;
use paintball\item\PaintballKits;
use paintball\utils\ArenaUtils;
use paintball\utils\Icons;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

// Import dependencies
require_once dirname(__DIR__, 2) . "/vendor/autoload.php";

class PaintballBase extends GameBase {

	public const TITLE = TextFormat::RED . TextFormat::BOLD . "PAINTBALL";

	public const LOBBIES_WORLD_PATH = "lobbies/";
	public const GENERATED_WORLD_PATH = "generated/";

	protected static PaintballBase $instance;


	protected PaintballLobbyEventHandler $lobbyEventHandler;
	protected HotbarMenu $lobbyMenu;
	/** @var array<string, Scoreboard> */
	protected array $scoreboards = [];
	protected DeployableClosure $scoreboardUpdate;

	protected function onLoad(): void {
		self::$instance = $this;
		parent::onLoad();
	}

	protected function onEnable() : void {
		$this->getArenaManager()->load();

		@mkdir($this->getServer()->getDataPath() . "worlds/" . self::GENERATED_WORLD_PATH);
		@mkdir($this->getServer()->getDataPath() . "worlds/" . self::LOBBIES_WORLD_PATH);

		LibCommandBase::register($this);

		$this->scoreboardUpdate = new DeployableClosure(
			closure: Closure::fromCallable([$this, "updateLobbyScoreboard"]),
			scheduler: $this->getScheduler()
		);
		$this->scoreboardUpdate->deploy(20);

		$this->lobbyEventHandler = new PaintballLobbyEventHandler($this);
		$this->lobbyEventHandler->register($this);

		$this->getServer()->getCommandMap()->registerAll(
			fallbackPrefix: $this->getName(),
			commands: [
				new CreateArenaCommand($this),
				new LogoCommand(),
				new InviteCommand($this),
				new SettingsCommand($this),
				new SpawnCommand(),
				new WorldCommand(),
			]
		);

		$this->getServer()->getPluginManager()->registerEvents(listener: new PaintballListener($this), plugin: $this);
	}

	protected function onDisable() : void {
		$this->clearScoreboards();
		$this->scoreboardUpdate->cancel();
		// Unload existing worlds
		foreach($this->getServer()->getWorldManager()->getWorlds() as $world) {
			if(str_contains(haystack: $world->getFolderName(), needle: self::GENERATED_WORLD_PATH) || str_contains(haystack: $world->getFolderName(), needle: self::LOBBIES_WORLD_PATH)) {
				$this->getServer()->getWorldManager()->unloadWorld($world);
			}
		}
		ArenaUtils::deleteDirectory($this->getServer()->getDataPath() . "worlds/" . self::GENERATED_WORLD_PATH);
	}

	public static function getInstance(): PaintballBase {
		return self::$instance;
	}

	/**
	 * @return array<string>
	 */
	public function getScoreboardData(): array {
		return self::formatScoreboard([
			TextFormat::WHITE . "Active Games: " . TextFormat::YELLOW . count($this->getGameManager()->getAll())
		]);
	}

	/**
	 * @param array<string> $data
	 * @return array<string>
	 */
	public static function formatScoreboard(array $data): array {
		$max = max(array_map(fn(string $line) => strlen(TextFormat::clean($line)), $data)) ?: 0;
		$outline = str_pad(string: Icons::SCOREBOARD_OUTLINE, length: (int) floor($max / 5), pad_type: STR_PAD_BOTH);
		return [
			$outline,
			...$data,
			$outline,
			TextFormat::YELLOW . str_pad(string: "valiantnetwork.xyz", length: $max, pad_type: STR_PAD_BOTH),
		];
	}

	/**
	 * @param array<string> $data
	 * @return array<string>
	 */
	public static function formatGameScoreboard(array $data): array {
		$max = max(array_map(fn(string $line) => strlen(TextFormat::clean($line)), $data)) ?: 0;
		return [
			Icons::GAME_SCOREBOARD_OUTLINE,
			...$data,
			Icons::GAME_SCOREBOARD_OUTLINE,
			TextFormat::YELLOW . str_pad(string: "valiantnetwork.xyz", length: $max, pad_type: STR_PAD_BOTH),
		];
	}

	/**
	 * @return array<Player>
	 */
	public function getLobbyPlayers(): array {
		return array_filter(
			array: $this->getServer()->getOnlinePlayers(),
			callback: fn(Player $player) => $this->getGameManager()->getGameByPlayer($player) === null
		);
	}

	/**
	 * Gets or creates a lobby scoreboard for a player
	 *
	 * @param Player $player
	 * @return Scoreboard
	 */
	public function getScoreboard(Player $player): Scoreboard {
		return $this->scoreboards[$player->getUniqueId()->getBytes()] ??= new Scoreboard(
			player: $player,
			title: self::TITLE,
			lines: $this->getScoreboardData()
		);
	}

	/**
	 * Removes the lobby scoreboard for the given player
	 *
	 * @param Player $player
	 * @return void
	 */
	public function removeScoreboard(Player $player): void {
		if(!isset($this->scoreboards[$player->getUniqueId()->getBytes()])) {
			return;
		}
		$scoreboard = $this->getScoreboard($player);
		$scoreboard->remove();
		unset($this->scoreboards[$player->getUniqueId()->getBytes()]);
	}

	/**
	 * Removes all scoreboards for all players
	 *
	 * @return void
	 */
	public function clearScoreboards(): void {
		foreach($this->scoreboards as $key => $scoreboard) {
			$scoreboard->remove();
			unset($this->scoreboards[$key]);
		}
	}

	public function updateLobbyScoreboard(): void {
		foreach($this->getLobbyPlayers() as $player) {
			$scoreboard = $this->getScoreboard($player);
			$scoreboard->setLines($this->getScoreboardData());
			if(!$scoreboard->isVisible()) {
				$scoreboard->send();
			} else {
				$scoreboard->update();
			}
		}
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
	public function generateArena(Closure $onSuccess, Closure $onFailure): void {
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
		$arena = new PaintballArena(
			name: $data->getName(),
			world: $world,
			firstSpawnpoint: $data->getFirstSpawnpoint(),
			secondSpawnpoint: $data->getSecondSpawnpoint(),
			generated: true
		);
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

	public function getHotbarMenu(): HotbarMenu {
		return $this->lobbyMenu ??= new HotbarMenu([
			0 => new MenuEntry(
				item: VanillaItems::BOOK()->setCustomName(TextFormat::YELLOW . "Game Selector"),
				closure: function(Player $player): void {
					if($this->getGameManager()->getGameByPlayer($player) !== null) {
						return;
					}
					/** @var array<PaintballGame> $games */
					$games = array_values(array_filter(
						array: $this->getGameManager()->getAll(),
						callback: fn(Game $game) => !$game instanceof CustomPaintballGame
					));
					$form = new SimpleForm(
						title: "Game Selector",
						buttons: array_map(
							callback: fn(PaintballGame $game) => new Button(
								text: TextFormat::YELLOW . "Game" . TextFormat::EOL . "Kit: " . TextFormat::LIGHT_PURPLE . $game->getKit()->getName() . TextFormat::YELLOW .  " | " . "State: " . $this->getNameFromState($game->getState()),
								onClick: function (Player $player) use($game): void {
									$this->removeScoreboard($player);
									$game->handleJoin($player);
								}),
							array: $games
						)
					);
					$form->send($player);
				}
			),
			1 => new MenuEntry(
				item: VanillaItems::PAPER()->setCustomName(TextFormat::YELLOW . "Create Game"),
				closure: function(Player $player): void {
					if(!$player->hasPermission((string) PaintballPermissions::CREATE())) {
						$player->sendMessage(TextFormat::RED . "You do not have permission to create games.");
						return;
					}
					/** @var PaintballArena|null $arena */
					$arena = $this->getArenaManager()->findOpenArena();
					if($arena !== null) {
						$this->getArenaManager()->setOccupied($arena, true);
						$game = new CustomPaintballGame(plugin: $this, uniqueId: uniqid(), arena: $arena, teamMode: TeamMode::SOLO(), lobbyWorld: $this->createLobby(), kit: PaintballKits::VANILLA(), leader: $player);
						$this->getGameManager()->add($game);
					} else {
						$player->sendMessage(TextFormat::YELLOW . "There are no open arenas at the moment. Attempting to generate one...");
						$this->generateArena(
							onSuccess: function(Arena $arena) use($player): void {
								/** @var PaintballArena $arena */
								$game = new CustomPaintballGame(plugin: $this, uniqueId: uniqid(), arena: $arena, teamMode: TeamMode::SOLO(), lobbyWorld: $this->createLobby(), kit: PaintballKits::VANILLA(), leader: $player);
								$this->getGameManager()->add($game);
								$player->sendMessage(TextFormat::GREEN . "Game generated successfully!");
								$game->handleJoin($player);
							},
							onFailure: function(string $errorMessage) use($player): void {
								$player->sendMessage(TextFormat::RED . "Failed to generate an arena: $errorMessage");
							}
						);
					}

				}
			)
		]);
	}
}