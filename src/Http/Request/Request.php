<?php namespace Chestnut\Http\Request;

  class Request implements \ArrayAccess, \IteratorAggregate
  {
    protected $property;

    public function __construct()
    {
      $env = [];

      $env['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

      $env['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];

      $env['ROOT_SCRIPT'] = $_SERVER['DOCUMENT_ROOT'];

      $requestUri = $_SERVER['REQUEST_URI'];
      $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

      $env['PATH_INFO'] = explode('?', $requestUri)[0];
      $env['REQUEST_URI'] = $requestUri;

      $env['QUERY_STRING'] = $queryString;

      $env['SERVER_NAME'] = $_SERVER['SERVER_NAME'];

      $env['SERVER_PORT'] = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;

      foreach($_SERVER as $key => $value) {
        if(strpos($key, 'HTTP_') === 0 || strpos($key, 'X_') === 0 || in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
          if($key === "CONTENT_LENGTH") continue;

          $result[$key] = $value;
        }
      }

      $env['POST'] = $_POST;
      $env['GET'] = $_GET;

      $this->property = $env;
    }

    public function offsetExists($offset)
    {
      return array_key_exists($this->property[$offset]);
    }

    public function offsetGet($offset)
    {
      return $this->property[$offset];
    }

    public function offsetSet($offset, $value)
    {
      $this->property[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
      unset($this->property[$offset]);
    }

    public function getIterator()
    {
      return new \ArrayIterator($this->property);
    }
  }
