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

namespace paintball\utils;

use pocketmine\utils\TextFormat;

class Column {

	/**
	 * @param array<string> $data
	 */
	public function __construct(protected array $data) {}

	/**
	 * @return string[]
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * Formats each column into padded strings
	 *
	 * @return array<string>
	 */
	public function format(): array {
		$max = max(array_map(fn(string $value) => strlen(TextFormat::clean($value)), $this->data));
		return array_map(
			callback: function (string $value) use($max): string {
				// pad string to max length with spaces without str_pad
				return $value . str_repeat(" ", $max - strlen(TextFormat::clean($value))) ;
			},
			array: $this->data
		);
	}
}