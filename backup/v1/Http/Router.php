<?php
namespace Cheatnut\Http;

class Router {

  protected $current;
  
  protected $application;

  protected $routes;
  
  public function __construct($application)
  {
    $this->routes = [];
    $this->application = $application;
  }

  public function addRoute($args) {
    $pattern = array_shift($args);
    $callable = array_pop($args);

    $route = new Route($pattern, $callable, $this->application);

    array_push($this->routes, $route);

    return $route;
  }

  public function match($method, $uri, $reload = false) {
    
    if($reload || is_null($this->current))
    {
      foreach($this->routes as $route)
      {
        if(! $route->supportHttpMethod($method) && ! $route->supportHttpMethod('ANY'))
        {
          continue;
        }
        
        if($route->matches($uri)) {
          $this->current = $route;
          return $this->current;
        }
      }
    }
    
  }

  public function current() {
    return $this->current;
  }

  public function __call($method, $params) {
    if(! in_array($method, ['get', 'post', 'delete', 'put', 'patch', 'any']))
    {
      throw new \Exception("Method [ $method ] not found in Route");
    }
    
    return $this->addRoute($params[0])->via(strtoupper($method));
  }

  public function dispatch() {
    $this->current->dispatch();
  }
}
