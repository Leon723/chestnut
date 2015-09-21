<?php namespace Chestnut\Core\Component;

use Closure;
use ArrayAccess;
use ReflectionClass;
use RuntimeException;

class Component implements ArrayAccess
{
  /**
   * 组件容器实例
   */
  protected static $instance;
  /**
   * 组件注册表
   *
   * @var array
   */
  protected $registry = [];

  /**
   * 共享组件储存
   *
   * @var array
   */
  protected $instances = [];

  /**
   * 组件实例状态储存
   *
   * @var array
   */
  protected $resolved = [];

  /**
   * 组件别名
   *
   * @var array
   */
  protected $aliases = [];

  /**
   * 注册组件
   *
   * @param string              $name     组件名称
   * @param Closure|string|null $builder  组件构造器
   * @param bool                $shared   是否共享组件
   *
   * @return void
   */
  public function register($name, $builder = null, $shared = false)
  {
    if(is_null($builder)) {
      $builder = $name;
    }

    $this->registry[$name] = compact('builder', 'shared');
  }

  /**
   * 注册单例模式组件
   *
   * @param string              $name     组件名称
   * @param Closure|string|null $builder  组件构造器
   *
   * @return void
   */
  public function singleton($name, $builder = null)
  {
    $this->register($name, $builder, true);
  }

  /**
   * 注册组件实例
   *
   * @param string  $name      组件名称
   * @param mixed   $instance  组件实例
   *
   * @return void
   */
  public function instance($name, $instance)
  {
    $this->instances[$name] = $instance;
  }

  /**
   * 注册组件别名
   *
   * @param string  $alias      组件别名
   * @param string  $component  组件名称
   *
   * @return void
   */
  public function alias($alias, $component)
  {
    $this->aliases[$alias] = $component;
  }

  /**
   * 查明是否已经注册组件
   *
   * @param string $name 组件名称
   *
   * @return bool
   */
  public function registered($name)
  {
    return isset($this->registry[$name]) || isset($this->instances[$name]) || $this->isAlias($name);
  }

  /**
   * 查明是否已经实例化组件
   *
   * @param string $name 组件名称
   *
   * @return bool
   */
  public function resolved($name)
  {
    return isset($this->resolved[$name]) && $this->resolved[$name];
  }

  /**
   * 查明是否存在别名
   *
   * @param string $alias
   *
   * @return bool
   */
  public function isAlias($alias)
  {
    return isset($this->aliases[$alias]);
  }

  /**
   * 实例化组件
   *
   * @param string $name        组件名称
   * @param array  $parameters  组件参数
   */
  public function make($name, $parameters = [])
  {
    $name = $this->getAlias($name);

    if(isset($this->instances[$name])) {
      return $this->instances[$name];
    }

    $component = $this->registry[$name];

    if(is_null($builder = $component['builder'])) {
      $builder = $name;
    }

    $object = $this->build($builder, $parameters);

    if($this->isShared($name)) {
      $this->instances[$name] = $object;
    }

    $this->resolved[$name] = true;

    return $object;
  }

  /**
   * 构造组件实例
   *
   * @param Closure|string $builder 组件构造器
   * @param array $parameters 组件参数
   *
   * @return mixed
   */
  public function build($builder, $parameters)
  {
    if(is_callable($builder)) {
      return $builder($this, $parameters);
    }

    $reflection = new ReflectionClass($builder);

    if(is_null($constructor = $reflection->getConstructor())) {
      return new $builder;
    }

    $dependencies = $constructor->getParameters();

    $instances = $this->getDependencies($dependencies, $parameters);

    return $reflection->newInstanceArgs($instances);
  }

  public function getDependencies($dependencies, $parameters)
  {
    $instances = [];
    foreach($dependencies as $dependency) {
      if($class = $dependency->getClass()) {
        $instances[] = $this->make($class->name);
      } else if(array_key_exists($dependency->name, $parameters)) {
        $instances[] = $parameters[$dependency->name];
      } else {
        $instances[] = $dependency->isDefaultValueAvailable() ? $dependency->getDefaultValue() : null;
      }
    }

    return $instances;
  }

  /**
   * 获取组件名
   *
   * @param string $alias 组件别名
   *
   * @return string
   */
  public function getAlias($alias)
  {
    return isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;
  }

  public function isShared($name)
  {
    return isset($this->registry[$name]) && $this->registry[$name]['shared'];
  }

  /**
   * 删除组件
   *
   * @param string $name 组件名
   *
   * @return bool
   */
  public function remove($name)
  {
    if($this->isAlias($name)) {
      $alias = $name;
      $name = $this->getAlias($name);

      unset($this->alias[$alias]);
    }

    if($this->resolved($name)) {
      unset($this->instances[$name]);
      unset($this->resolved[$name]);
    }

    if($this->registered($name)) {
      unset($this->registry[$name]);
    }
  }

  public static function setInstance(Component $instance)
  {
    static::$instance = $instance;
  }

  public static function getInstance()
  {
    return static::$instance;
  }

  /**
   * 实现数组接口
   */
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
