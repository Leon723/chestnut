<?php namespace Chestnut\Core\Route;

  use Chestnut\Application;

  class RouteProvider
  {
    protected $app;
    protected $routes;
    protected $current;

    public function __construct(Application $app)
    {
      $this->app = $app;
      $this->routes = [];
    }

    public function appendRoute($args)
    {
      $pattern = array_shift($args);
      $callable = array_pop($args);
      $middleware = $args;

      $route = new Route($pattern, $middleware, $callable);

      $this->routes[$pattern] = $route;

      return $route;
    }

    public function matches($reload = false) {

      if($reload || is_null($this->current))
      {
        foreach($this->routes as $path => $route)
        {
          if(! $route->supportMethod($this->app['request']->method()) && ! $route->supportMethod('ANY'))
          {
            continue;
          }

          if($path === $this->app['request']->path()) {
            $this->current = $route;
            break;
          }

          if($route->match($this->app['request'])) {
            $this->current = $route;
            break;
          }
        }
      }
    }

    public static function register($app)
    {
      $app->singleton(static::class);
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

    public function dispatch() {
      $result = $this->current->dispatch($this->app);

      return $result;
    }

    public function dispatchable()
    {
      return ! is_null($this->current);
    }
  }
