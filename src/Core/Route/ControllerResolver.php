<?php namespace Chestnut\Core\Route;

use Closure;
use ReflectionMethod;
use ReflectionFunction;
use Chestnut\Core\View\View;

class ControllerResolver
{
  protected $controller;
  protected $dependencies;

  public function __construct($controller)
  {
    $this->controller = $controller;
  }

  public function resolve()
  {
    if($this->controller instanceof Closure) {
      $reflector = new ReflectionFunction($this->controller);
    } else {
      list($class, $method) = explode("@", $this->controller);

      $class = config()->has('app.controller')
             ? config('app.controller') . $class
             : "App\\Controllers\\" . $class;

      $reflector = new ReflectionMethod($class, $method);
      $this->controller = $reflector->getClosure(new $class);
    }

    $parameters = $reflector->getParameters();
    $dependencies = [];

    foreach($parameters as $parameter) {
      if($p_class = $parameter->getClass()) {
        $dependencies[$parameter->name] = $p_class;
      } elseif($parameter->isDefaultValueAvailable()) {
        $dependencies[$parameter->name] = $parameter->getDefaultValue();
      } else {
        $dependencies[$parameter->name] = null;
      }
    }

    $this->dependencies = $dependencies;
  }

  public function getDependencies()
  {
    return is_null($this->dependencies) ? [] : $this->dependencies;
  }

  public function dispatch($dependencies)
  {
    if(is_callable($this->controller)) {
      ob_start();
      $result = call_user_func_array($this->controller, $dependencies);

      if($result instanceof View) {
        $result->display();
      }

      return ob_get_clean();
    }
  }
}
