<?php namespace Cheatnut\Http\Request;

  class RequestProvider
  {
    private static $instance = null;
    protected $request;

    private function __construct()
    {
      $this->request = new Request();
    }

    public static function getInstantce()
    {
      if(static::$instance === null) {
        return static::$instance = new self();
      }

      return static::$instance;
    }

    public function getUrl()
    {
      return $this->request['PATH_INFO'];
    }

    public function getMethod()
    {
      return $this->request['REQUEST_METHOD'];
    }

    public function getParameter($key = null, $type = 'get')
    {
      if($key === null) {
        return null;
      }

      $input = $type === 'input' ? array_merge($this->request['GET'], $this->request['POST']) : $this->request[strtoupper($type)];

      if(! array_key_exists($key, $input))
      {
        throw new \RuntimeException("The parameter [$key] not found in input");
      }

      return $input[$key];
    }

    public function getRoot()
    {
      return $this->request['ROOT_SCRIPT'];
    }

    public static function __callstatic($method, $params)
    {
      if(! in_array($method, ['get', 'post', 'input'])) {
        throw new \RuntimeException("call to an undefined method [$method]");
      }

      return \Cheatnut\Core\Registry::get('request')->getParameter($params[0], $method);
    }

    public function isHead()
    {
      return $this->getMethod() === 'HEAD';
    }
  }
