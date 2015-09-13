<?php namespace Chestnut\Staticizer;

class Staticizer
{
  public static function __callStatic($method, $parameters)
  {
    return call_user_func_array([app(static::getAccessor()), $method], $parameters);
  }
}
