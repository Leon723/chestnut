<?php namespace Chestnut\Http\Route;

  use \Chestnut\Core\Registry;

  class RouteProvider
  {
    protected $routes;
    protected $current;

    public function __construct()
    {
      $this->routes = [];
    }

    public function appendRoute($args)
    {
      $pattern = array_shift($args);
      $callable = array_pop($args);

      $route = new Route($pattern, $callable);

      $this->routes[$pattern] = $route;

      return $route;
    }

    public function match($method, $uri, $reload = false) {

      if($reload || is_null($this->current))
      {
        foreach($this->routes as $path => $route)
        {
          if(! $route->supportMethod($method) && ! $route->supportMethod('ANY'))
          {
            continue;
          }

          if($path === $uri) {
            $this->current = $route;
          }

          if($route->match($uri)) {
            $this->current = $route;
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
        throw new \RuntimeException("Call to undefined method [$method] in Route");
      }

      return $this->appendRoute($params)->via(strtoupper($method));
    }

    public static function __callstatic($method, $params)
    {
      if(! in_array($method, ['get', 'post', 'delete', 'put', 'patch', 'any']))
      {
        throw new \RuntimeException("Call to undefined method [$method] in Route");
      }

      $r = Registry::get('route');

      return $r->appendRoute($params)->via(strtoupper($method));
    }

    public function dispatch() {
      $result = $this->current->dispatch();

      if($result instanceof \Cheatnut\Http\View\View) {
        return $result->display();
      }

      return $result;
    }
  }
