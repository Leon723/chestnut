<?php namespace Chestnut\Core\Config;

use ArrayAccess;

class Config implements ArrayAccess
{
  /**
   * 配置存储
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * 设置属性
   *
   * @param string  $key    属性名
   * @param mixed   $value  属性值
   *
   * @return void
   */
  public function set($key, $value)
  {
    array_dot_set($this->attributes, $key, $value);
  }

  /**
   * 获取属性
   *
   * @param string $key     属性名
   * @param string $default 无法获取时的默认返回值
   *
   * @return mixed
   */
  public function get($key, $default = null)
  {
    if(isset($this->attributes[$key])) {
      return $this->attributes[$key];
    }

    return array_dot_get($this->attributes, $key, $default);
  }

  /**
   * 删除属性
   *
   * @param string $key 属性名
   *
   * @return void
   */
  public function remove($key)
  {
    array_dot_set($this->attributes,$key);
  }

  /**
   * 查明是否拥有属性
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
   * 数组接口实现
   */
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
}
