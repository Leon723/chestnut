<?php namespace Cheatnut\Core;
  
class Container implements \ArrayAccess, \Countable, \IteratorAggregate
{
  protected $data = [];
  
  public function __construct(Array $item = [])
  {
    $this->replace($item);
  }
  
  public function set($key, $value)
  {
    $this->data[$key] = $value;
  }
  
  public function get($key, $default = null)
  {
    if($this->has($key)) {
      $isInvokable = is_object($this->data[$key]) && method_exists($this->data[$key], '__invoke');
      
      return $isInvokable ? $this->data[$key]($this) : $this->data[$key];
    }
    
    return $default;
  }
  
  public function has($key)
  {
    return array_key_exists($key, $this->data);
  }
  
  public function all()
  {
    return $this->data;
  }
  
  public function replace($items)
  {
    foreach($items as $key => $value) {
      $this->set($key, $value);
    }
  }
  
  public function keys() 
  {
    return array_keys($this->data);
  }
  
  public function remove($key)
  {
    unset($this->data[$key]);
  }
  
  /**
    * Property Overloading
    */
  
  public function __get($key)
  {
    return $this->get($key);
  }
  
  public function __set($key, $value)
  {
    $this->set($key, $value);
  }
  
  public function __isset($key)
  {
    return $this->has($key);
  }
  
  public function __unset($key)
  {
    $this->remove($key);
  }
  
  /**
    * Array Access
    */
  
  public function offsetExists($offset)
  {
    return $this->has($offset);
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
  
  /**
    * Clear All values
    */
  
  public function clear()
  {
    $this->data = [];
  }
  
  /**
    * Countable
    */
  
  public function count()
  {
    return count($this->data);
  }
  
  public function getIterator()
  {
    return new \ArrayIterator($this->data);
  }
  
  public function singleton($key, $value)
  {
    $this->set($key, function($c) use($value) {
      static $object;
      
      if(null === $object) {
        $object = $value($c);
      }
      
      return $object;
    });
  }
}