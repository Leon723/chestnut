<?php namespace Chestnut\Contract\Support;

interface Container
{
  /**
   * Register component
   *
   * @param string              $name     name of Component
   * @param Closure|string|null $builder  builder of Component
   * @param bool                $share    share Component
   *
   * @return void
   */
  public function register($name, $builder, $share);

  /**
   * Register component instance
   *
   * @param string  $name     name of component
   * @param mixed   $instance instance of component
   *
   * @return void
   */
  public function instance($name, $instance);

  /**
   * Register singleton component
   *
   * @param string              $name     name of component
   * @param Closure|string|null $builder  builder of component
   *
   * @return void
   */
  public function singleton($name, $builder);

  /**
   * Resolve Component
   *
   * @param string  $name       component name or alias
   * @param array   $parameters component parameter
   *
   * @return mixed
   */
  public function make($name, $parameters);

  /**
   * Build Component instance
   *
   * @param string  $builder    builder of component
   * @param array   $parameters parameter of component
   *
   * @return mixed
   */
  public function build($builder, $parameters);

  /**
   * Register component's alias
   *
   * @param string $alias component's alias
   * @param string $name  component's name
   *
   * @return void
   */
  public function alias($alias, $name);

  /**
   * get component's name by alias
   *
   * @param string $alias component's alias
   *
   * @return string
   */
  public function getAlias($alias);

  /**
   * Remove component
   *
   * @param string $name component's name
   */
  public function remove($name);

  /**
   * Determine component is registered
   *
   * @param string $name component's name
   *
   * @return boolean
   */
  public function registered($name);

  /**
   * Determine component is resolved
   *
   * @param string $name component's name
   *
   * @return boolean
   */
  public function resolved($name);

  /**
   * Determine component has alias
   *
   * @param string $alias component's alias
   *
   * @return boolean
   */
  public function isAlias($alias);

  /**
   * Determine component is share
   *
   * @param string $name component's name
   *
   * @return boolean
   */
  public function isShared($name);

  public static function setInstance(Container $app);

  public static function getInstance();
}
