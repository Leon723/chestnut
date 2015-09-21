<?php namespace Chestnut\Support;

use ArrayAccess;

class Component implements ArrayAccess
{
  /**
   * 注册组件储存
   *
   * @var Parameter
   */
  protected $register;

  /**
   * 组件是否实例化储存
   *
   * @var Parameter
   */
  protected $resolved;

  /**
   * 组件实例储存
   *
   * @var Parameter
   */
  protected $instances;

  /**
   * 组件别名储存
   *
   * @var Parameter
   */
  protected $aliases;

  /**
   * 构造组件容器
   */
  public function __construct()
  {
    $this->register = new Parameter();
    $this->resolved = new Parameter();
    $this->instances = new Parameter();
    $this->aliases = new Parameter();
  }

  /**
   * 注册组件
   *
   * @param string              $name     组件名称
   * @param Closure|string|null $builder  组件构造器
   * @param bool                $share    是否共享组件
   *
   * @return void
   */
  public function register($name, $builder = null, $share = false)
  {
    if(is_null($builder)) {
      $builder = $name;
    }

    $this->register->set($name, compact('builder', 'share'));
  }

  /**
   * 查明组件是否已注册
   *
   * @param string $name 组件名称
   *
   * @return bool
   */
  public function registered($name)
  {
    return $this->register->has($name) || $this->instances->has($name) || $this->aliases->has($name);
  }

  /**
   * 查明组件是否已实例化
   *
   * @param string $name 组件名称
   *
   * @return bool
   */
  public function resolved($name)
  {
    return $this->resolved->has($name) || $this->instances->has($name);
  }

  /**
   * 实例化组件
   *
   * @param string  $name       组件名称
   * @param array   $parameters 组件参数
   *
   * @return Component
   */
  public function make($name, $parameters = [])
  {
    $name = $this->getAlias($name);

    if($this->resolved($name)) {
      return $this->instances[$name];
    }

    $builder = $this->register->get("$name.builder");

    $obj = $this->build($builder, $parameters);

    if($this->isShared($name)) {
      $this->instances->set($name, $obj);
    }

    $this->resolved->set($name, true);

    return $obj;
  }

  /**
   * 创建组件实例
   *
   * @param Closure|string $builder     组件构造器
   * @param array          $parameters  组件参数
   *
   * @return Component
   */
  public function build($builder, $parameters = [])
  {
    $cr = new ControllerResolver($builder);
    $cr->resolve();

    $dependencies = $cr->getDependencies();
    $instances = [];

    foreach($dependencies as $name=> $dependency) {
      if($this->registered->has($dependency)) {
        $instances[$name] = $this[$dependency];
      } elseif(array_key_exists($name, $parameters)) {
        $instances[$name] = $parameters[$name];
      } else {
        $instances[$name] = $dependency;
      }
    }

    return $cr->build($instances);
  }

  /**
   * 注册组件别名
   *
   * @param string $alias 组件别名
   * @param string $name  组件名称
   *
   * @return void
   */
  public function alias($alias, $name)
  {
    $this->aliases->set($alias, $name);
  }

  /**
   * 查明组件是否为共享组件
   *
   * @param string $name 组件名称
   *
   * @return bool
   */
  public function isShared($name)
  {
    return $this->register->has("$name.share") && $this->register->get("$name.share");
  }

  /**
   * 获取组件别名
   *
   * @param string $name 组件名称
   *
   * @return string
   */
  public function getAlias($name)
  {
    return $this->aliases->get($name, $name);
  }

  /**
   * 查明是否注册别名
   *
   * @param string $alias
   *
   * @return bool
   */
  public function isAlias($alias)
  {
    return $this->aliases->has($alias);
  }

  /**
   * 移除组件
   */
  public function remove($name)
  {
    if($this->isAlias($name)) {
      $alias = $name;
      $name = $this->getAlias($name);

      $this->aliases->remove($alias);
    }

    if($this->resolved($name)) {
      $this->resolved->remove($name);
      $this->instances->remove($name);
    }

    if($this->registered($name)) {
      $this->register->remove($name);
    }
  }

  public function offsetSet($key, $value)
  {
    $this->instance($key, $value);
  }

  public function offsetGet($key)
  {
    return $this->make($key);
  }

  public function offsetExists($key)
  {
    return $this->registered($key);
  }

  public function offsetUnset($key)
  {
    $this->remove($key);
  }

  public function __get($key)
  {
    return $this[$key];
  }

  public function __set($key, $value)
  {
    $this[$key] = $value;
  }

}
