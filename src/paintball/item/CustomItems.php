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

namespace paintball\item;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static Item CROSSBOW()
 */
class CustomItems {
	use CloningRegistryTrait;

	protected static function setup(): void {
		self::register("crossbow", ItemFactory::getInstance()->get(ItemIds::CROSSBOW));
	}

	protected static function register(string $name, Item $item): void {
		self::_registryRegister($name, $item);
	}
}