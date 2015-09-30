<?php namespace Chestnut\Http;

use Closure;
use Chestnut\Support\Parameter;
use Chestnut\Application\Application;

class Router
{
  protected $app;

  /**
   * 路由存储
   *
   * @var Chestnut\Support\Parameter
   */
  protected $routes;

  /**
   * 路由命名存储
   *
   * @var Chestnut\Support\Parameter
   */
  protected $named;

  /**
   * 组合队列
   *
   * @var array
   */
  protected $groupStack = [];

  public function __construct(Application $app)
  {
    $this->app = $app;
    $this->routes = new Parameter();
    $this->named  = new Parameter();
  }

  /**
   * 添加路由
   *
   * @param array $args 路由配置
   *
   * @return Chestnut\Http\Route
   */
  public function addRoute($method, $args)
  {
    list($url, $options) = $this->convertRouteOption($args);

    $route = new Route($method, $url, $options);

    $this->routes->set($url, $route);

    return $route;
  }

  public function group($options, Closure $callable)
  {
    $this->groupStack[] = $options;

    $callable($callable);

    array_pop($this->groupStack);
  }

  public function convertRouteOption($args)
  {
    $url = array_shift($args);
    $options = array_pop($args);

    if($this->hasGroupStack()) {
      $group = end($this->groupStack);
    }

    if(! is_array($options)) {
      $options = ['controller'=> $options];
    }

    if(count($args) === 1) {
      $options['middleware'] = $args;
    }

    if(isset($group)) {
      $options = array_merge($options, $group);
    }

    $url = $this->convertRouteName($url);

    return [$url, $options];
  }

  public function convertRouteName($url)
  {
    if(is_array($url)) {
      $name = current($url);
      $url = key($url);
    }

    if($this->hasGroupStack()) {
      $group = end($this->groupStack);

      $url = $group['prefix'] . $url;
    }

    if(isset($name)) {
      $this->setName($name, $url);
    }

    return $url;
  }

  public function hasGroupStack()
  {
    return ! empty($this->groupStack);
  }
  /**
   * 命名路由
   *
   * @param string 路由名称
   * @param string 路由路径
   *
   * @return void
   */
  public function setName($name, $url)
  {
    $this->named->set($name, $url);
  }

  /**
   * 匹配路由
   *
   * @return void
   */
  public function match()
  {
    foreach($this->routes as $route) {
      if($route->match($this->app->request)) {
        return $this->app->instance('current', $route);
      }
    }
  }

  public function routes()
  {
    return $this->routes;
  }

  public function __call($method, $params)
  {
    if(! in_array($method, ['get', 'post', 'delete', 'put', 'patch', 'option', 'trace', 'any'])) {
      throw new \RuntimeException("call to an undefined method [$method] on " . static::class);
    }

    if($method === 'get') {
      $method = ['head', 'get'];
    }

    if($method === 'any') {
      $method = ['get', 'post', 'delete', 'put', 'patch', 'option', 'trace'];
    }

    return $this->addRoute($method, $params);
  }
}
