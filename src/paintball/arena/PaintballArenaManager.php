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

use paintball\PaintballBase;
use pocketmine\utils\AssumptionFailedError;

class PaintballArenaManager {

	protected const FILE_NAME = "arenas.json";

	/** @var array<int, PaintballArena> */
	protected array $arenas = [];
	/** @var array<int, bool> */
	protected array $occupiedArenas = [];

	public function __construct(protected PaintballBase $plugin) {}

	public function load(): void {
		if (!file_exists($this->plugin->getDataFolder() . self::FILE_NAME)) {
			return;
		}

		/** @var array<int, array<string, mixed>> $data */
		$data = json_decode(json: file_get_contents(filename: $this->plugin->getDataFolder() . self::FILE_NAME) ?: throw new AssumptionFailedError("File does not exist"), associative: true);
		foreach ($data as $rawArenaData) {
			$this->add(PaintballArena::create(PaintballArenaData::unmarshal($rawArenaData)));
		}
		$this->plugin->getLogger()->info("Loaded " . count($this->arenas) . " arenas");
	}

	public function add(PaintballArena $arena): void {
		$this->arenas[spl_object_id($arena)] = $arena;
	}

	public function remove(PaintballArena $arena): void {
		unset($this->arenas[spl_object_id($arena)]);
	}

	public function setOccupied(PaintballArena $arena, bool $occupied): void {
		if($occupied) {
			$this->occupiedArenas[spl_object_id($arena)] = true;
		} else {
			unset($this->occupiedArenas[spl_object_id($arena)]);
		}
	}

	public function isOccupied(PaintballArena $arena): bool {
		return isset($this->occupiedArenas[spl_object_id($arena)]);
	}

	public function findOpen(): ?PaintballArena {
		foreach($this->arenas as $arena) {
			if(!$this->isOccupied($arena)) {
				return $arena;
			}
		}
		return null;
	}

	public function save(): void {
		$data = [];
		foreach ($this->arenas as $arena) {
			$data[] = (new PaintballArenaData(
				world: $arena->getWorld(),
				firstSpawnpoint: $arena->getFirstSpawnpoint(),
				secondSpawnpoint: $arena->getSecondSpawnpoint()
			))->marshal();
		}
		file_put_contents(filename: $this->plugin->getDataFolder() . self::FILE_NAME, data: json_encode($data, JSON_PRETTY_PRINT));
	}


}