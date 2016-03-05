<?php
namespace Chestnut\Support;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Cache extends File {
	public static function makeDir($path = '') {
		$path = app()->cachePath() . $path;

		parent::makeDir($path);

		chmod($path, 0777);
	}

	public static function read($type, $fileName) {
		if (!static::exists($type)) {
			return false;
		}

		$path = app()->cachePath() . $type . DIRECTORY_SEPARATOR . $fileName;

		return parent::readFile($path);
	}

	public static function write($type, $filename, $content) {
		if (!static::exists($type)) {
			static::makeDir($type);
		}

		$path = app()->cachePath() . $type . DIRECTORY_SEPARATOR . join(explode(DIRECTORY_SEPARATOR, $filename), '.');

		return parent::writeFile($path, $content);
	}
}