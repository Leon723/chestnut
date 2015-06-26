<?php
namespace Cheatnut\Route;

class Router {

  protected static $current;

  protected static $routes = [];

  protected static function addRoute($args) {
    $pattern = array_shift($args);
    $callable = array_pop($args);

    $route = new Route($pattern, $callable);

    array_push(self::$routes, $route);

    return $route;
  }

  public static function match($request) {

    foreach(self::$routes as $route){
      if($route->matches($request)) {
        self::$current = $route;
        break;
      }
    }
  }

  public static function current() {
    return self::$current;
  }

  public static function __callstatic($method, $params) {
    return self::addRoute($params)->via(strtoupper($method));
  }

  public static function dispatch() {
    self::$current->dispatch();
  }
}
