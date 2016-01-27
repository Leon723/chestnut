<?php namespace Chestnut\Contract\Support;

interface File
{
  /**
   * Read files in directory
   * @param  string $path   Directory Path
   * @param  string $filter File type
   * @return array
   */
  public static function readDir($path, $filter = null);

  public static function makeDir($path);

  public static function readFile($path);

  public static function writeFile($path, $content);

  public static function file_diff_time($file1, $file2);
}