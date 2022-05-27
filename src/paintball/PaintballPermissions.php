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

use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\EnumTrait;

/**
 * @method static PaintballPermissions CREATE()
 */
class PaintballPermissions {
	use EnumTrait {
		EnumTrait::__construct as private Enum__construct;
		EnumTrait::register as private Enum_register;
	}

	/**
	 * @return void
	 */
	protected static function setup(): void {
		self::register(new PaintballPermissions("create", "paintball.create", "Allows the player to create a paintball game"));
	}

	protected static function register(PaintballPermissions $member, bool $addToOperatorGroup = true): void {
		self::Enum_register($member);

		PermissionManager::getInstance()->addPermission(new Permission(
			name: $member->permissionName(),
			description: $member->description(),
			children: array_fill_keys($member->children(), true)
		));

		if($addToOperatorGroup) {
			$group = PermissionManager::getInstance()->getPermission(DefaultPermissionNames::GROUP_OPERATOR) ?? throw new AssumptionFailedError("Operator group does not exist");
			$group->addChild($member->permissionName(), true);
		}
	}

	/**
	 * @param string $enumName
	 * @param string $permissionName
	 * @param string|null $description
	 * @param array<string> $children
	 */
	public function __construct(string $enumName, protected string $permissionName, protected ?string $description = null, protected array $children = []) {
		$this->Enum__construct($enumName);
	}

	public function permissionName(): string {
		return $this->permissionName;
	}

	public function description(): ?string {
		return $this->description;
	}

	/**
	 * @return array<string>
	 */
	public function children(): array {
		return $this->children;
	}

	public function __toString(): string {
		return $this->permissionName;
	}
}