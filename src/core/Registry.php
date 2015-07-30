<?php namespace Cheatnut\Core;

  class Registry
  {
    protected static $registry = [];
    protected static $shared = [];
    protected static $parameters = [];

    public static function register($name, $resolve)
    {
      static::$registry[$name] = $resolve;
    }

    public static function resolve($name, $params)
    {
      if(is_array(static::$registry[$name]) && array_key_exists('class', static::$registry[$name])) {
        static $obj;

        $obj = new static::$registry[$name]['class'];

        $params = array_merge_recursive(static::$registry[$name]['parameters'], $params);

        $obj->setParameters($params);

        return $obj;
      }

      if(is_callable(static::$registry[$name])) {
        static $obj;

        $obj = call_user_func(static::$registry[$name], $params);

        return $obj;
      }

      throw new \RuntimeException('The component [$name] not found in Registry, please check whether you registered');
    }

    public static function resolveShared($name, $params, $refresh = false)
    {
      if(isset(static::$shared[$name]) && ! $refresh) {
        return static::$shared[$name];
      }

      if(is_array(static::$registry[$name]) && array_key_exists('class', static::$registry[$name])) {
        static $obj;

        $obj = new static::$registry[$name]['class'];

        $params = array_merge_recursive(static::$registry[$name]['parameters'], $params);

        $obj->setParameters($params);

        return static::$shared[$name] = $obj;
      }

      if(is_callable(static::$registry[$name])) {
        static $obj;

        $obj = call_user_func(static::$registry[$name], $params);

        return static::$shared[$name] = $obj;
      }

      throw new \RuntimeException('The component [$name] not found in Registry, please check whether you registered');
    }

    public static function make($name, $params = [])
    {
      return static::resolve($name, $params);
    }

    public static function get($name, $params = [])
    {
      return static::resolveShared($name, $params);
    }

    public static function registered($name)
    {
      return array_key_exists($name, static::$registrt[$name]);
    }

    public static function setParameters($name, $params)
    {
      if(is_array(static::$registry[$name])) {
        static::$registry[$name]['parameters'] = $params;
      }
      else {
        static::$parameters[$name] = $params;
      }
    }
  }
