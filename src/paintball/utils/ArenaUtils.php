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

final class ArenaUtils {

	public static function copyDirectory(string $source, string $destination): bool {
		try {
			$dir = opendir($source);
			@mkdir($destination);
			while(($file = readdir($dir))) {
				if ($file !== "." && $file !== "..") {
					if (is_dir("$source/$file") ) {
						self::copyDirectory("$source/$file", "$destination/$file");
					} else {
						copy("$source/$file","$destination/$file");
					}
				}
			}
			closedir($dir);
			return true;
		} catch(Exception $exception) {
			var_dump($exception->getMessage());
			return false;
		}
	}

	public static function deleteDirectory(string $directory) {
		$files = new RecursiveIteratorIterator(
			iterator: new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
			mode: RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ($files as $fileInfo) {
			$todo = ($fileInfo->isDir() ? "rmdir" : "unlink");
			$todo($fileInfo->getRealPath());
		}

		rmdir($directory);
	}

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