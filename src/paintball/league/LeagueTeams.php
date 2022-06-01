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

namespace paintball\league;

use pocketmine\utils\EnumTrait;

/**
 * @method static self SAN_JOSE()
 * @method static self OAKLAND()
 * @method static self PORT_HURON()
 * @method static self ALASKA()
 * @method static self FLORIDA()
 * @method static self CINCINNATI()
 * @method static self NEW_YORK()
 * @method static self BOSTON()
 * @method static self MONTREAL()
 * @method static self CHICAGO()
 * @method static self LONDON()
 * @method static self CALIFORNIA()
 */
final class LeagueTeams {
	use EnumTrait {
		EnumTrait::__construct as private Enum__construct;
	}

	protected static function setup(): void {
		self::register(new self("SAN_JOSE", "\u{E300}", "San Jose Slashers", ["sanjose", "slashers"]));
		self::register(new self("OAKLAND", "\u{E301}", "Oakland Renegades", ["oakland", "renegades"]));
		self::register(new self("PORT_HURON", "\u{E302}", "Port Huron Panthers", ["porthuron", "panthers"]));
		self::register(new self("ALASKA", "\u{E303}", "Alaska Aces", ["alaska", "aces"]));
		self::register(new self("FLORIDA", "\u{E304}", "Florida Flamingos", ["florida", "flamingos"]));
		self::register(new self("CINCINNATI", "\u{E305}", "Cincinnati Flames", ["cincinnati", "flames"]));
		self::register(new self("NEW_YORK", "\u{E306}", "New York Storm", ["newyork", "storm"]));
		self::register(new self("BOSTON", "\u{E307}", "Boston Blitz", ["boston", "blitz"]));
		self::register(new self("MONTREAL", "\u{E308}", "Montreal Recons", ["montreal", "recons"]));
		self::register(new self("CHICAGO", "\u{E309}", "Chicago Carbines", ["chicago", "carbines"]));
		self::register(new self("LONDON", "\u{E30A}", "London Monarchs", ["london", "monarchs"]));
		self::register(new self("CALIFORNIA", "\u{E30B}", "California Hellhounds", ["california", "hellhounds"]));
	}

	/** @var array<string, LeagueTeams> */
	public static array $aliasMapping = [];

	/**
	 * @param string $enumName
	 * @param string $logo
	 * @param string $formattedName
	 * @param array<string> $aliases
	 */
	public function __construct(string $enumName, protected string $logo, protected string $formattedName, protected array $aliases) {
		$this->Enum__construct($enumName);
		foreach($aliases as $alias) {
			self::$aliasMapping[$alias] = $this;
		}
	}

	/**
	 * @return string
	 */
	public function getLogo(): string {
		return $this->logo;
	}

	public function getFormattedName(): string {
		return $this->formattedName;
	}

	/**
	 * @return array<string>
	 */
	public function getAliases(): array {
		return $this->aliases;
	}

	public static function fromAlias(string $name): ?LeagueTeams {
		return self::$aliasMapping[strtolower($name)] ?? null;
	}
}