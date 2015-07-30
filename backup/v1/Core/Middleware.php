<?php namespace Cheatnut\Core;
  
abstract class Middleware
{
  protected $app;
  
  protected $next;
  
  final public function setApplication($application)
  {
    $this->app = $application;
  }
  
  final public function getApplication()
  {
    return $this->app;
  }
  
  final public function setNextMiddleware($nextMiddleware)
  {
    $this->next = $nextMiddleware;
  }
  
  final public function getNextMiddleware()
  {
    return $this->next;
  }
  
  abstract public function call();
}