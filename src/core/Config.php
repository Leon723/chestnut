<?php namespace Cheatnut\Core;

class Config implements \ArrayAccess, \Countable, \IteratorAggregate
{
  protected $property;

  public function __construct($defaults, $config)
  {
    $this->property = array_merge_recursive($defaults, $config);
  }

  public function set($key, $value)
  {
    $this->property[$key] = $value;
  }

  public function get($key)
  {
    return $this->property[$key];
  }

  public function remove($key)
  {
    unset($this->property[$key]);
  }

  public function has($key)
  {
    return array_key_exists($key, $this->property);
  }

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

  public function count()
  {
    return count($this->property);
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->property);
  }
}
