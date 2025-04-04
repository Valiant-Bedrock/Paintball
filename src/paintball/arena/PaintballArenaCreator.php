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

namespace paintball\arena;

use libforms\buttons\Button;
use libforms\CustomForm;
use libforms\elements\Input;
use libforms\elements\Label;
use libforms\ModalForm;
use paintball\PaintballBase;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class PaintballArenaCreator implements Listener {

	protected PaintballArenaCreatorState $state;

	protected string $name;
	protected World $world;
	protected Vector3 $firstSpawnpoint;
	protected Vector3 $secondSpawnpoint;

	public function __construct(protected Player $creator, protected PaintballBase $plugin) {
		$this->state = PaintballArenaCreatorState::FIRST_SPAWNPOINT();
	}

	public function getCreator(): Player {
		return $this->creator;
	}

	public function setState(PaintballArenaCreatorState $state): void {
		$this->state = $state;
		$this->handleMode();
	}

	public function start(): void {
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
		$this->handleMode();
	}


	public function handleMode(): void {
		switch($this->state->id()) {
			case PaintballArenaCreatorState::FIRST_SPAWNPOINT()->id():
				$this->getCreator()->sendMessage(TextFormat::GREEN . "Please click the first spawnpoint.");
				break;
			case PaintballArenaCreatorState::SECOND_SPAWNPOINT()->id():
				$this->getCreator()->sendMessage(TextFormat::GREEN . "Please click the second spawnpoint.");
				break;
			case PaintballArenaCreatorState::SET_NAME()->id():
				$form = new CustomForm(
					title: "Set Arena Name",
					elements: [
						new Label(text: "Please enter the name of the arena."),
						new Input(
							text: "Arena Name",
							placeholder: "Arena Name",
							default: $this->world->getFolderName(),
							callable: function(string $value): void {
								$this->name = $value ?: $this->world->getDisplayName();
								$this->finish();
							}
						)
					]
				);
				$form->send($this->getCreator());
				break;
		}
	}

	public function handleBreak(BlockBreakEvent $event): void {
		if($event->getPlayer() !== $this->getCreator()) {
			return;
		}
		$event->cancel();
		$position = $event->getBlock()->getPosition();
		switch($this->state->id()) {
			case PaintballArenaCreatorState::FIRST_SPAWNPOINT()->id():
				$form = new ModalForm(
					title: "Confirm Selection",
					content: "Are you sure you want to set this as the first spawnpoint?",
					primaryButton: new Button("Confirm", function (Player $player) use($position): void {
						$this->world = $position->getWorld();
						$this->firstSpawnpoint = $position->asVector3();
						$this->setState(PaintballArenaCreatorState::SECOND_SPAWNPOINT());
						$player->sendMessage(TextFormat::GREEN . "First spawnpoint successfully set at $position");
					}),
					secondaryButton: new Button("Cancel", function (Player $player): void { $player->sendMessage(TextFormat::YELLOW . "Spawnpoint cancelled") ;})
				);
				$form->send($this->getCreator());
				break;
			case PaintballArenaCreatorState::SECOND_SPAWNPOINT()->id():
				$form = new ModalForm(
					title: "Confirm Selection",
					content: "Are you sure you want to set this as the second spawnpoint?",
					primaryButton: new Button("Confirm", function (Player $player) use($position): void {
						$this->secondSpawnpoint = $position->asVector3();
						$player->sendMessage(TextFormat::GREEN . "Second spawnpoint successfully set at $position");
						$this->setState(PaintballArenaCreatorState::SET_NAME());
					}),
					secondaryButton: new Button("Cancel", function (Player $player): void { $player->sendMessage(TextFormat::YELLOW . "Spawnpoint cancelled") ;})
				);
				$form->send($this->getCreator());
				break;
		}
	}

	public function finish(): void {
		$this->plugin->getArenaManager()->add(PaintballArena::create(new PaintballArenaData(
			name: $this->name,
			world: $this->world,
			firstSpawnpoint: $this->firstSpawnpoint,
			secondSpawnpoint: $this->secondSpawnpoint
		)));
		$this->plugin->getArenaManager()->save();
		HandlerListManager::global()->unregisterAll($this);
		$this->getCreator()->sendMessage(TextFormat::GREEN . "Arena successfully created and saved!");
	}

}