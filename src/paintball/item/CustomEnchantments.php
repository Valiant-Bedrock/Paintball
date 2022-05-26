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

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\RegistryTrait;

/**
 * @method static Enchantment QUICK_CHARGE()
 * @method static Enchantment MULTISHOT()
 */
class CustomEnchantments {
	use RegistryTrait;

	protected static function setup(): void {
		self::register("quick_charge", EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::QUICK_CHARGE) ?? throw new AssumptionFailedError("Enchantment does not exist"));
		self::register("multishot", EnchantmentIdMap::getInstance()->fromId(EnchantmentIds::MULTISHOT) ?? throw new AssumptionFailedError("Enchantment does not exist"));
	}

	protected static function register(string $name, Enchantment $enchantment) : void{
		self::_registryRegister($name, $enchantment);
	}
}