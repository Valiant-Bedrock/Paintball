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

use DirectoryIterator;
use Exception;
use FilesystemIterator;
use paintball\PaintballBase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ArenaUtils {

	public static function copyDirectory(string $source, string $destination): bool {
		try {
			$directory = opendir($source) ?: throw new RuntimeException("Unable to open source directory $source");
			@mkdir($destination);
			while(($file = readdir($directory))) {
				if ($file !== "." && $file !== "..") {
					if (is_dir("$source/$file") ) {
						self::copyDirectory("$source/$file", "$destination/$file");
					} else {
						copy("$source/$file","$destination/$file");
					}
				}
			}
			closedir($directory);
			return true;
		} catch(Exception $exception) {
			var_dump($exception->getMessage());
			return false;
		}
	}

	public static function deleteDirectory(string $directory): void {
		$files = new RecursiveIteratorIterator(
			iterator: new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
			mode: RecursiveIteratorIterator::CHILD_FIRST
		);

		/** @var SplFileInfo $fileInfo */
		foreach ($files as $fileInfo) {
			$todo = ($fileInfo->isDir() ? "rmdir" : "unlink");
			$todo($fileInfo->getRealPath());
		}

		rmdir($directory);
	}

	/**
	 * @return array<string>
	 */
	public static function getTemplates(): array {
		$templates = [];
		foreach(new DirectoryIterator(PaintballBase::getInstance()->getDataFolder() . "arena_templates") as $file) {
			if($file->isDir() && !$file->isDot()) {
				$templates[] = $file->getFilename();
			}
		}
		return $templates;
	}

}