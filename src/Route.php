<?php

namespace Yound;

class Route {

  protected $pattern;

  protected $path;

  protected $callable;

  protected $conditions = [];

  protected $params = [];

  protected $methods = [];

  public function __construct($pattern, $callable) {
    $this->setPattern($pattern);
    $this->_setPath();
    $this->setCallable($callable);
  }

  public function getPattern() {
    return $this->pattern;
  }

  public function setPattern($pattern) {

    $this->pattern = $pattern;
  }

  public function getPath() {
    return $this->path;
  }

  protected function _setPath() {
    $pos = strpos($this->pattern, ":");
    if($pos) {
      $this->path = substr($this->pattern, 0, $pos);
    }
    else {
      $this->path = $this->pattern;
    }
  }

  public function getCallable() {
    return $this->callable;
  }

  public function setCallable($callable) {
    if(is_string($callable) && $matches = explode(':', $callable)) {
      $class = $matches[0];
      $method = $matches[1];

      $callable = function () use($class, $method) {
        static $obj = null;
        if($obj === null) {
          $obj = new $class;
        }
        return call_user_func([$obj, $method], func_get_args());
      };
    }


    if(!is_callable($callable)) throw new \InvalidArgumentException('Route callable must be callable');

    $this->callable = $callable;
  }

  public function getConditions() {
    return $this->conditions;
  }

  public function setConditions(array $conditions) {
    $this->conditions = $conditions;
  }

  public function getParams() {
    return $this->params;
  }

  public function setParams($params) {
    $paramNames = [];
    preg_match_all('#:([\w]+)?#', $this->pattern, $paramNames);

    foreach($paramNames[1] as $key => $name){
      if(!isset($params[$key])){
        $this->params[$name] = null;
        continue;
      }

      $this->params[$name] = $params[$key];
    }
  }

  public function getHttpMethods() {
    return $this->methods;
  }

  public function setHttpMethods($methods) {
    $this->methods = $methods;
  }

  public function appendHttpMethods() {
    $args = func_get_args();
    if(count($args) && is_array($args[0])){
      $args = $args[0];
    }

    $this->methods = array_merge($this->methods, $args);
  }

  public function via() {
    $args = func_get_args();

    $this->appendHttpMethods($args);
  }

  public function supportHttpMethod($method) {
    return in_array($method, $this->methods);
  }


  public function matches($uri, $method) {
    $uriPath = substr($uri, 0, strlen($this->path));
    $uriPath = (substr($uriPath, -1) === '/' ? $uriPath : $uriPath . '/');

    if($this->path !== $uriPath) return false;
    if(! $this->supportHttpMethod($method)) return false;

    $paramString = substr($uri, strlen($this->path));

    $this->setParams(explode('/', $paramString));

    return true;
  }

  public function dispatch() {
    $result = call_user_func_array($this->getCallable(), array_values($this->getParams()));
    return $result === false ? false : true;
  }
}