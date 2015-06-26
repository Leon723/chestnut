<?php
namespace Cheatnut\Request;

class Request {
  protected static $query = [];
  protected $method;
  protected $uri;
  protected $ip;
  protected $host;

  public function __construct() {
    $this->__init();
  }

  protected function __init() {
    $this->uri = explode("?", $_SERVER['REQUEST_URI'])[0];
    $this->ip = $_SERVER['REMOTE_ADDR'];
    $this->host = $_SERVER['HTTP_HOST'];
    $this->method = $_SERVER['REQUEST_METHOD'];

    if(strlen($_SERVER['QUERY_STRING']) !== 0) {
      $queryString = explode("&",$_SERVER['QUERY_STRING']);

      foreach($queryString as $param) {
        $param = explode("=", $param);

        self::$query[$param[0]] = $param[1];
      }
    }
  }
  
  public function appendParams($params) {
    self::$query = array_merge(self::$query, $params);
  }

  public function getUri() {
    return $this->uri;
  }

  public function getMethod() {
    return $this->method;
  }

  public static function __callstatic($key, $params) {
    if(! isset(self::$query[strtolower($key)])) {
      throw new \Exception("Call to undefined method $key()");
    }
    
    return self::$query[strtolower($key)];
  }
}
