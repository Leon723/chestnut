<?php

namespace Cheatnut\Http;

class Route {
  
  protected $pattern;

  protected $path;

  protected $callable;

  protected $conditions = [];

  protected $params = [];

  protected $methods = [];

  public function __construct($pattern, $callable, $application) {
    $this->setPattern($pattern);
    $this->setCallable($callable, $application);
    $this->setParams();
  }

  public function getPattern() {
    return $this->pattern;
  }

  public function setPattern($pattern) {
    $pos = strpos($pattern, ":");
    if($pos) {
      $this->path = substr($pattern, 0, $pos);
      $this->pattern = substr($pattern, $pos);
    }
    else {
      $this->path = $this->pattern = $pattern;
    }
  }

  public function getPath() {
    return $this->path;
  }

  public function getCallable() {
    return $this->callable;
  }

  public function setCallable($callable, $application) {
    if(is_string($callable) && $matches = explode(':', $callable)) {
      $class = 'App\\Controllers\\' . $matches[0];
      $method = $matches[1];

      $callable = function () use($class, $method, $application) {
        static $obj = null;
        if($obj === null) {
          $obj = new $class($application);
        }
        return call_user_func_array([$obj, $method], func_get_args());
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

  public function setParams($params = null) {
    
    if(is_array($params) && count($this->params) == 0) return false;
    
    preg_match_all('#:([\w]+)?#', $this->pattern, $paramNames);
    
    if(is_null($params))
    {
      foreach($paramNames[1] as $key){
        $this->params[$key] = null;
      }
      
      return true;
    }

    foreach($paramNames[1] as $key => $name){
      if(isset($this->conditions[$name]) && ! preg_match('#' . $this->conditions[$name] . '#', $params[$key])) {
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
    if(count($args) && is_array($args[0])) {
      $args = $args[0];
    }
    if(in_array('ANY', $args)) {
      $args = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    }

    $this->methods = array_merge($this->methods, $args);
  }

  public function via() {
    $args = func_get_args();

    $this->appendHttpMethods($args);

    return $this;
  }

  public function supportHttpMethod($method) {
    return in_array($method, $this->methods);
  }


  public function matches($uri) {
    $uriPath = substr($uri, 0, strlen($this->path));
    
    if($this->path !== $uriPath) return false;

    $params = explode('/', substr($uri, strlen($this->path)));

    if(count($params) < count($this->params)) return false;
    
    $this->setParams($params);

    return true;
  }

  public function condition(Array $conditions) {
    $this->setConditions($conditions);

    return $this;
  }

  public function dispatch() {
    $result = call_user_func_array($this->getCallable(), array_values($this->getParams()));
    return $result;
  }
}
