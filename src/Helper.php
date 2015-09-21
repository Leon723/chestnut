<?php

use Chestnut\Core\Component\Component;

if(! function_exists('app')) {
  function app($component = null, $parameters = []) {
    if(is_null($component)) {
      return Component::getInstance();
    }

    return Component::getInstance()->make($component, $parameters);
  }
}

if(! function_exists('config')) {
  function config($key = null, $default = null) {
    if(! is_null($key)) {
      return app('config')->get($key, $default);
    } else {
      return app('config');
    }
  }
}

if(! function_exists('view')) {
  function view($filename, $data = []) {
    return app('view')->make($filename, $data);
  }
}

if(! function_exists('array_dot_get')) {
  /**
   * Get value by "." in array
   *
   * @param array $array
   * @param string $key
   * @param string $default
   *
   * @return mixed
   */
  function array_dot_get($array, $key, $default = null)
  {
    foreach(explode(".", $key) as $segment) {
      if(! array_key_exists($segment, $array)) {
        return $default;
      }

      $array = $array[$segment];

    }

    return $array;
  }
}

if(! function_exists('array_dot_set')) {
  function array_dot_set(&$array, $key, $value = null)
  {
    $keys = explode(".", $key);

    while(count($keys) >= 1) {
      $key = array_shift($keys);

      if(! isset($array[$key]) || ! is_array($array[$key])) {
        $array[$key] = [];
      }

      if(count($keys) === 0 && is_null($value)) {
        unset($array[$key]);
      } else {
        $array = &$array[$key];

        $array = $value;
      }
    }
  }
}
