<?php namespace Chestnut\Contract\Support;

interface ControllerBuilder
{
  /**
   * Analysis the class to get reflector
   * @param  string|Closure $class ControllerClass
   * @return void
   */
  public function analysis($class);

  /**
   * get Reflector
   * @return Reflector
   */
  public function getReflector();

  /**
   * Inject controller require component or parameter
   * @param  Container $c          Component container
   * @param  array     $parameters Parameter container
   * @return void                
   */
  public function inject(Container $c, $parameters = []);

  /**
   * Instantiated class
   * 
   * @param  array  $dependencies class dependencies
   * @return mixed               
   */
  public function build();

  /**
   * Determine inject finished
   * @return boolean
   */
  public function injected();

  /**
   * Determine inject needle
   * @return boolean
   */
  public function isNeedInject();
}
