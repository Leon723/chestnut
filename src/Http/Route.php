<?php namespace Chestnut\Http;

use ArrayAccess;
use Chestnut\Http\Request;
use Chestnut\Support\Parameter;
use Chestnut\Support\ControllerResolver;
use Chestnut\Application\Application;

class Route implements ArrayAccess
{
  /**
   * 路由属性存储
   *
   * @var Chestnut\Support\Parameter
   */
  protected $attributes;

  /**
   * 构造路由实例
   *
   * @param string  $url    路由路径
   * @param array   $option 路由配置
   */
  public function __construct($method, $url, $options = [])
  {
    $this->attributes = new Parameter($options);
    $this->method($method);

    $this['url'] = $url;
  }

  /**
   * 匹配路由
   *
   * @param Chestnut\Support\Request $request 请求实例
   *
   * @return bool
   */
  public function match(Request $request)
  {
    // if(! $this->matchDomain($request->domain())) {
    //   return false;
    // }

    if(! $this->supportMethod($request->method())) {
      return false;
    }

    if(! $this->matchUrl($request)) {
      return false;
    }

    if(! $this->parseUrl($request->path())) {
      return false;
    }

    return true;
  }

  public function dispatch(Application $app)
  {
    if(is_callable($this['controller'])) {
      $controller = $this['controller'];
    } else {
      $namespace = $this['namespace'] ? $this['namespace'] : 'App\\Controllers\\';
      $controller = $namespace . $this['controller'];
    }

    $resolver = new ControllerResolver($controller);

    $resolver->resolve();

    $dependencies = $resolver->getDependencies();
    $instances = [];

    foreach($dependencies as $name=> $dependency) {
      if(is_string($dependency) && $app->registered($dependency)) {
        $instances[$name] = $app[$dependency];
      } elseif(array_key_exists($name, $this['parameters'])) {
        $instances[$name] = $this['parameters'][$name];
      } elseif($app->registered($name)) {
        $instances[$name] = $app[$name];
      } else {
        $instances[$name] = $dependency;
      }
    }

    return $resolver->build($instances);
  }

  /**
   * 添加参数条件
   *
   * @param array|string  $key        参数键名
   * @param regexString   $condition  参数匹配条件
   */
  public function condition($key, $condition = null)
  {
    if(is_array($key)) {
      foreach($key as $name => $condition) {
        $this->condition($name, $condition);
      }
      return $this;
    }

    $this->attributes->add("condition", [$key, $condition]);
    return $this;
  }

  /**
   * 添加路由支持的请求方法
   *
   * @param string $method 请求方法
   *
   * @return void
   */
  public function method($method)
  {
    if(is_array($method)) {
      foreach($method as $name) {
        $this->method($name);
      }
      return $this;
    }

    $this->attributes->add('method', [strtoupper($method)=> true]);
    return $this;
  }

  /**
   * 查明路由是否支持请求方法
   *
   * @param string $method 请求方法
   *
   * @return bool
   */
  public function supportMethod($method)
  {
    return $this->attributes->has("method." . strtoupper($method)) && $this["method." . strtoupper($method)];
  }

  public function offsetSet($key, $value)
  {
    $this->attributes[$key] = $value;
  }

  public function offsetGet($key)
  {
    return $this->attributes[$key];
  }

  public function offsetExists($key)
  {
    return $this->attributes->has($key);
  }

  public function offsetUnset($key)
  {
    $this->attributes->remove($key);
  }

  private function matchDomain($domain)
  {
    if(! $this['domain']) {
      return true;
    }

    if($this['domain'] !== $domain) {
      return false;
    }

    return true;
  }
  /**
   * 匹配路径
   *
   * @param Chestnut\Http\Request $request 请求实例
   *
   * @return bool
   */
  private function matchUrl(Request $request)
  {
    if(! $url = strstr($this['url'], ':', true))
    {
      $url = $this['url'];
    }

    $url = strlen($url) > 1 ? rtrim($url, '/') : $url;

    if($url !== $request->path() && $url !== substr($request->path(), 0, strpos($this['url'], "/:"))) {
      return false;
    }

    return true;
  }

  /**
   * 解析 URL 获取参数
   *
   * @param string $url
   *
   * @return bool
   */
  private function parseUrl($url)
  {
    $params = $this->getParams($this['url']);

    $urlParamString = substr($url, strpos($this['url'], "/:"));
    $urlParams = $this->getParams($urlParamString, true);

    if(count($params) >= 1 && count($params) !== count($urlParams)) {
      return false;
    }

    $result = [];

    foreach($params as $index=> $key) {
      if(array_key_exists($key, $result)) {
        throw new \RuntimeException("The parameter [$key] has already exists, please check your route url");
      }

      $key = explode('@', $key);

      if($this->attributes->has('condition.' . $key[0])) {
        $key[1] = $this["condition." . $key[0]];
      }

      if(count($key) > 1 && preg_match("/$key[1]+/", $urlParams[$index])) {
        $result[$key[0]] = $urlParams[$index];
      } else if(count($key) === 1) {
        $result[$key[0]] = $urlParams[$index];
      } else {
        return false;
      }
    }

    $this['parameters'] = $result;

    return true;
  }

  /**
   * 获取路径上的参数
   *
   * @param string  $url        路径
   * @param bool    $isRequest  是否为请求路径
   *
   * @return array
   */
  private function getParams($url, $isRequest = false)
  {
    $segment = $isRequest ? "/" : "/:";

    $params = explode($segment, $url);

    array_shift($params);

    return $params;
  }
}
