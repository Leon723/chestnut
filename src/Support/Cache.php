<?php namespace Chestnut\Support;

class Cache extends File
{
  private static function isExistsDir($dirName = '')
  {
    if(! is_dir(app()->cachePath())) {
      static::makeDir();
    }

    if(! is_dir(app()->cachePath() . $dirName)) {
      static::makeDir($dirName);
    }
  }

  public static function makeDir($path = '')
  {
    $path = app()->cachePath() . $path;

    parent::makeDir($path);

    chmod($path, 0777);
  }

  public static function read($type, $fileName)
  {
    static::isExistsDir($type);

    $path = app()->cachePath() . $type . DIRECTORY_SEPARATOR . $fileName;

    return parent::readFile($path);
  }

  public static function write($type, $filename, $content)
  {
    static::isExistsDir($type);

    $path = app()->cachePath() . $type . DIRECTORY_SEPARATOR . $filename;

    return parent::writeFile($path, $content);
  }
}