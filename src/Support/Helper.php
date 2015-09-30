<?php

use Chestnut\Support\Component;

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

if(! function_exists('redirect')) {
  function redirect($url, $status = 302, $header = []) {
    return app('response')->redirect($url, $status, $header);
  }
}
