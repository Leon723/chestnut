<?php namespace Chestnut\Support;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

class Parameter implements ArrayAccess, IteratorAggregate
{
  /**
   * 属性存储
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * 构造参数对象
   *
   * @param array $attributes 初始化参数
   */
  public function __construct($attributes = [])
  {
    $this->replace($attributes);
  }

  /**
   * 重置属性
   *
   * @param array $attributes 重置参数
   *
   * @return void
   */
  public function replace($attributes)
  {
    $this->attributes = (array) $attributes;
  }

  /**
   * 获取所有属性
   *
   * @return array
   */
  public function all()
  {
    return $this->attributes;
  }

  /**
   * 获取所有属性键名
   *
   * @return array
   */
  public function keys()
  {
    return array_keys($this->attributes);
  }

  /**
   * 设置属性
   *
   * @param string $key   属性名
   * @param mixed  $value 属性值
   *
   * @return void
   */
  public function set($key, $value)
  {
    $array = &$this->attributes;

    $keys = explode(".", $key);

    while(count($keys) > 1) {
      $key = array_shift($keys);

      if(! isset($array[$key]) || ! is_array($array[$key])) {
        $array[$key] = [];
      }

      $array = &$array[$key];
    }

    $key = array_shift($keys);

    $array[$key] = $value;
  }

  /**
   * 获取属性值
   *
   * @param string  $key        属性名
   * @param mixed   $default    默认返回值
   * @param bool    $reference  是否引用属性
   *
   * @return mixed
   */
  public function get($key, $default = null, $reference = false)
  {
    $array = $reference ? &$this->attributes : $this->attributes;

    foreach(explode(".", $key) as $segment) {
      if(! array_key_exists($segment, $array)) {
        return $default;
      }

      $array = $reference ? &$array[$segment] : $array[$segment];
    }

    return $array;
  }

  /**
   * 查明是否包含属性
   *
   * @param string $key 属性名
   *
   * @return bool
   */
  public function has($key)
  {
    return ! is_null($this->get($key));
  }

  /**
   * 移除属性
   *
   * @param string $key 属性名
   */
  public function remove($key)
  {
    unset($this->get($key, null, true));
  }

  public function offsetSet($key, $value)
  {
    $this->set($key, $value);
  }

  public function offsetGet($key)
  {
    return $this->get($key);
  }

  public function offsetExists($key)
  {
    return $this->has($key);
  }

  public function offsetUnset($key)
  {
    $this->remove($key);
  }

  public function count($key = null)
  {
    return is_null($key) ? count($this->arrtibutes) : count($this->get($key));
  }

  public function getIterator()
  {
    return new ArrayIterator($this->attributes);
  }
}
