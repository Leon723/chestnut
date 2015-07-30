<?php namespace Cheatnut\Core;
  
class Environment implements \ArrayAccess, \IteratorAggregate
{
  protected $data;
  
  private static $env;
  
  public static function getInstance($refresh = false)
  {
    if(is_null(self::$env) || $refresh) {
      return self::$env = new self();
    }
    
    return self;
  }
  
  private function __construct()
  {
    $env = [];
    
    $env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];
    
    $env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    
    $requestUri = $_SERVER['REQUEST_URI'];
    $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    
    $env['PATH_INFO'] = explode('?', $requestUri)[0];
    $env['REQUEST_URI'] = $requestUri;
    
    $env['QUERY_STRING'] = $queryString;
    
    $env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];
    
    $env['SERVER_PORT'] = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
    
    $header = \Cheatnut\Http\Header::extra($_SERVER);
    foreach($header as $key => $value) {
      $env[$key] = $value;
    }
    
    $this->data = $env;
  }
  
  public function offsetExists($offset)
  {
    return array_key_exists($offset);
  }
  
  public function offsetGet($offset)
  {
    if(isset($this->data[$offset])) {
      return $this->data[$offset];
    }
    
    return null;
  }
  
  public function offsetSet($offset, $value)
  {
    $this->data[$offset] = $value;
  }
  
  public function offsetUnset($offset)
  {
    unset($this->data[$offset]);
  }
  
  public function getIterator() {
    return new \ArrayIterator($this->data);
  }
}