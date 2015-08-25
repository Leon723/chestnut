<?php namespace Chestnut\Core;

class Config implements \ArrayAccess, \Countable, \IteratorAggregate
{
  /**
   * 属性
   * @var array
   */
  protected $property;

  /**
   * 构造函数
   * @param array $defaults 默认设置
   * @param array $config   用户设置
   */
  public function __construct($defaults = [], $config = [])
  {
    $this->property = array_merge_recursive($defaults, $config);
  }

  /**
   * 设置属性
   * @param string $key   属性名
   * @param any $value 属性值
   */
  public function set($key, $value)
  {
    $this->property[$key] = $value;
  }

  /**
   * 获取属性值
   * @param  string $key 属性名
   * @return any      属性值
   */
  public function get($key)
  {
    return $this->property[$key];
  }

  /**
   * 删除属性
   * @param  string $key 属性名
   */
  public function remove($key)
  {
    unset($this->property[$key]);
  }

  /**
   * 是否拥有某属性
   * @param  string  $key 属性名
   * @return boolean
   */
  public function has($key)
  {
    return array_key_exists($key, $this->property);
  }

  /**
   * 实现数组接口
   */
  public function offsetGet($offset)
  {
    return $this->get($offset);
  }

  public function offsetSet($offset, $value)
  {
    $this->set($offset, $value);
  }

  public function offsetUnset($offset)
  {
    $this->remove($offset);
  }

  public function offsetExists($offset)
  {
    $this->has($offset);
  }

  /**
   * 实现属性计数接口
   */
  public function count()
  {
    return count($this->property);
  }

  /**
   * 返回遍历器
   */
  public function getIterator()
  {
    return new \ArrayIterator($this->property);
  }
}
