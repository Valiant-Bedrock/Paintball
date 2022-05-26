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

use libgame\kit\Kit;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\VanillaItems;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static Kit VANILLA()
 */
final class PaintballKits {
	use CloningRegistryTrait;

	protected static function setup(): void {
		self::register("vanilla", new Kit(
			name: "Vanilla",
			armor: [
				VanillaItems::LEATHER_CAP(),
				VanillaItems::LEATHER_TUNIC(),
				VanillaItems::LEATHER_PANTS(),
				VanillaItems::LEATHER_BOOTS()
			],
			items: [
				0 => CustomItems::CROSSBOW()->addEnchantment(new EnchantmentInstance(enchantment: CustomEnchantments::QUICK_CHARGE(), level: 3)),
				8 => VanillaItems::ARROW()->setCount(16)
			]
		));
	}

	protected static function register(string $name, Kit $kit) : void{
		self::_registryRegister($name, $kit);
	}
}