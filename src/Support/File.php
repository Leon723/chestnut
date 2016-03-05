<?php
namespace Chestnut\Support;

use Chestnut\Contract\Support\File as FileContract;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class File implements FileContract {
	public static function readDir($path, $filter = 'php') {
		if (!is_dir($path)) {
			return false;
		}

		if ($dir = opendir($path)) {
			$result = [];

			while (false !== ($file = readdir($dir))) {
				if (preg_match("/([\S\s]+?).$filter$/", $file)) {
					$result[] = ['fileName' => basename($file, '.' . $filter), 'path' => $file];
				}
			}

			closedir($dir);

			return $result;
		} else {
			return false;
		}
	}

	public static function getDir($path) {
		if (!is_dir($path)) {
			return false;
		}

		if ($dir = opendir($path)) {
			$result = [];

			while (false !== ($file = readdir($dir))) {
				if (is_dir($path . DIRECTORY_SEPARATOR . $file) && preg_match("/[^.]/", $file)) {
					$result[] = ['path' => $file];
				}
			}

			closedir($dir);

			return $result;
		} else {
			return false;
		}
	}

	public static function makeDir($path) {
		if (!is_dir($path)) {
			mkdir($path);

			return true;
		}

		return false;
	}

	public static function exists($path) {
		return file_exists($path);
	}

	public static function readFile($path) {
		return file_get_contents($path);
	}

	public static function writeFile($path, $content) {
		return file_put_contents($path, $content);
	}

	public static function diffTime($file1, $file2) {
		if (!static::exists($file2)) {
			return -1;
		}

		return filemtime($file1) - filemtime($file2);
	}
}