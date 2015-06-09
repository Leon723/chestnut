<?php
namespace Yound;

class Router {

  protected static $currentRoute;

  protected static $routes = [];

  protected static function addRoute($args) {
    $pattern = array_shift($args);
    $callable = array_pop($args);

    $route = new Route($pattern, $callable);

    array_push(self::$routes, $route);

    return $route;
  }

  public static function getRoute($resourceUri, $method) {

    foreach(self::$routes as $route){
      if($route->matches($resourceUri, $method)) {
        self::$currentRoute = $route;
        break;
      }
    }
  }

  public static function __callstatic($method, $params) {
    self::addRoute($params)->via($method);
  }

  public static function dispatch() {
    self::$currentRoute->dispatch();
  }
}