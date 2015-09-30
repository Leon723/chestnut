<?php namespace Chestnut\Support;

use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;

class ControllerResolver
{
  /**
   * 需要解析的控制器
   *
   * @var mixed
   */
  protected $controller;

  /**
   * 依赖注入存储
   *
   * @var array
   */
  protected $dependencies = [];

  /**
   * 构造控制器解析器
   *
   * @param mixed $controller 需要解析的控制器
   */
  public function __construct($controller)
  {
    $this->controller = $controller;
  }

  public function resolve()
  {
    if(is_callable($this->controller)) {
      $reflector = new ReflectionFunction($this->controller);
    } else {
      $controller = explode("::", $this->controller);

      switch(count($controller)) {
        case 2:
          $reflector = new ReflectionMethod($controller[0], $controller[1]);
          $this->controller = $reflector->getClosure(new $controller[0]);
          break;
        default:
          $this->controller = new ReflectionClass($controller[0]);
          $reflector = $this->controller->getConstructor();
          break;
      }
    }

    foreach($reflector->getParameters() as $parameter) {
      if($parameter->getClass()) {
        $this->dependencies[$parameter->name] = $parameter->getClass()->name;
      } elseif($parameter->isDefaultValueAvailable()) {
        $this->dependencies[$parameter->name] = $parameter->getDefaultValue();
      } else {
        $this->dependencies[$parameter->name] = null;
      }
    }
  }

  public function build($dependencies = [])
  {
    if(is_callable($this->controller)) {
      return call_user_func_array($this->controller, $dependencies);
    }

    return $this->controller->newInstanceArgs($dependencies);
  }

  public function getDependencies()
  {
    return is_null($this->dependencies) ? [] : $this->dependencies;
  }
}
