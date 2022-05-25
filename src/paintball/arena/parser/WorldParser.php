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

namespace paintball\arena\parser;

use libMarshal\parser\Parseable;
use pocketmine\Server;
use pocketmine\world\World;
use RuntimeException;

/**
 * @implements Parseable<string, World>
 */
class WorldParser implements Parseable {

	/**
	 * @param string $value
	 * @return World
	 */
	public function parse(mixed $value): World {
		$worldManager = Server::getInstance()->getWorldManager();
		$worldManager->loadWorld($value);
		return $worldManager->getWorldByName($value) ?? throw new RuntimeException("World '$value' not found");
	}

	/**
	 * @param World $value
	 * @return string
	 */
	public function serialize(mixed $value): string {
		return $value->getFolderName();
	}
}