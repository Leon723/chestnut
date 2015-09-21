<?php namespace Chestnut\Core\Route;

use ReflectionClass;

class Route
{
  protected $pattern;

  protected $controller;

  protected $conditions;

  protected $methods = [];

  protected $middleware = [];

  protected $parameters = [];

  public function __construct($pattern, $middleware, $controller = null)
  {
    if(is_null($controller)) {
      $controller = $middleware;
      $middleware = [];
    }

    $this->setPattern($pattern);
    $this->setMiddleware($middleware);
    $this->setController($controller);
  }

  public function setPattern($pattern)
  {
    $this->pattern = rtrim(preg_replace_callback("#<([\w]+)(?:\:?(.*?))>#", function($m) { return $this->parsePattern($m); }, $pattern), "?");
  }

  public function setMiddleware($middleware)
  {
    $this->middleware = $middleware;
  }

  public function setController($controller)
  {
    $this->controller = new ControllerResolver($controller);
  }

  public function setMethods($methods) {
    if(! is_array($methods)) {
      $methods = func_get_args();
    }

    $this->methods = $methods;
  }

  public function appendMethod($method)
  {
    $this->methods[] = $method;
  }

  public function supportMethod($method)
  {
    return in_array($method, $this->methods);
  }

  public function via($method)
  {
    $this->appendMethod($method);
  }

  public function match($request)
  {
    if(preg_match("#^$this->pattern$#", $request->path(), $match)) {
      foreach($this->parameters as $key=> $value) {
        $this->parameters[$key] = $match[$key];
      }

      return $this;
    }

    return false;
  }

  public function dispatch($app)
  {
    $this->controller->resolve();

    $dependencies = $this->getDependencies($app);

    return $this->controller->dispatch($dependencies);
  }

  public function getDependencies($app)
  {
    $parameters = [];

    foreach($this->controller->getDependencies() as $key=> $dependency) {
      if(! is_null($dependency) && $app->registered($dependency)) {
        $parameters[$key] = $app->make($dependency);
      } elseif(array_key_exists($key, $this->parameters)) {
        $parameters[$key] = $this->parameters[$key];
      } else {
        $parameters[$key] = $dependency;
      }
    }

    return $parameters;
  }

  public function parsePattern($match)
  {
    if($match[2] === '') {
      $match[2] = ".";
    }

    $this->parameters[$match[1]] = null;

    return "(?<$match[1]>$match[2]+?)";
  }
}
