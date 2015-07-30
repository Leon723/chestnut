<?php namespace Cheatnut\Http;
  
class Request implements \ArrayAccess, \IteratorAggregate
{
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  const METHOD_PUT = 'PUT';
  const METHOD_PATCH = 'PATCH';
  const METHOD_DELETE = 'DELETE';
  const METHOD_HEAD = 'HEAD';
  
  protected $header;
  
  protected $env;
  
  public function __construct($env)
  {
    $this->env = $env;
    $this->header = new Header(Header::extra($env));
  }
  
  public function getMethod()
  {
    return $this->env['REQUEST_METHOD'];
  }
  
  public function getUri()
  {
    return $this->env['PATH_INFO'];
  }
  
  public function isHead()
    {
      return $this->env['REQUEST_METHOD'] === static::METHOD_HEAD;
    }
  
  public function offsetExists($offset)
  {
    return array_key_exists($this->env[$offset]);
  }
  
  public function offsetGet($offset)
  {
    return $this->env[$offset];
  }
  
  public function offsetSet($offset, $value)
  {
    $this->env[$offset] = $value;
  }
  
  public function offsetUnset($offset)
  {
    unset($this->env[$offset]);
  }
  
  public function getIterator()
  {
    return new \ArrayIterator($this->env);
  }
}