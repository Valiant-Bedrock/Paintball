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

use libgame\arena\ArenaManager;
use paintball\PaintballBase;
use pocketmine\utils\AssumptionFailedError;

class PaintballArenaManager extends ArenaManager {

	protected const FILE_NAME = "arenas.json";

	/** @var array<PaintballArenaData> */
	protected array $arenaData = [];

	public function __construct(protected PaintballBase $plugin) {}

	public function load(): void {
		if (!file_exists($this->plugin->getDataFolder() . self::FILE_NAME)) {
			return;
		}
		/** @var array<int, array<string, mixed>> $data */
		$data = json_decode(json: file_get_contents(filename: $this->plugin->getDataFolder() . self::FILE_NAME) ?: throw new AssumptionFailedError("File does not exist"), associative: true);
		foreach($data as $current) {
			$arenaData = PaintballArenaData::unmarshal($current);
			$this->arenaData[$arenaData->getWorld()->getFolderName()] = $arenaData;
		}
		$this->plugin->getLogger()->info("Loaded " . count($this->arenas) . " arenas");
	}

	public function save(): void {
		$data = [];
		/** @var PaintballArena $arena */
		foreach ($this->arenas as $arena) {
			$data[] = (new PaintballArenaData(
				name: $arena->getName(),
				world: $arena->getWorld(),
				firstSpawnpoint: $arena->getFirstSpawnpoint(),
				secondSpawnpoint: $arena->getSecondSpawnpoint()
			))->marshal();
		}
		file_put_contents(filename: $this->plugin->getDataFolder() . self::FILE_NAME, data: json_encode($data, JSON_PRETTY_PRINT));
	}

	public function getArenaDataByWorldName(string $worldName): ?PaintballArenaData {
		return $this->arenaData[$worldName] ?? null;
	}


}