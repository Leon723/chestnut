<?php namespace Chestnut\Contract\Support;

interface Parameter
{
  public function replace($value);

  public function & get($key, $default, $reference);

  public function set($key, $value);

  public function push($key, $value);

  public function add($key, $value);

  public function & reference($key);

  public function has($Key);

  public function remove($key);
}
