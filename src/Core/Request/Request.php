<?php namespace Chestnut\Core\Request;

use Chestnut\Application;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request
{
  protected $app;

  protected $request;

  public function __construct(Application $app)
  {
    $this->app = $app;
    $this->request = SymfonyRequest::createFromGlobals();
    $this->request->enableHttpMethodParameterOverride();
  }

  public static function register(Application $app)
  {
    $app->singleton(static::class);
  }

  public function ip()
  {
    return $this->request->getClientIp();
  }

  public function ips()
  {
    return $this->request->getClientIps();
  }

  public function get($key, $default = null)
  {
    return $this->request->query->get($key, $default);
  }

  public function post($key, $default = null)
  {
    return $this->request->request->get($key, $default);
  }

  public function path()
  {
    return $this->request->getPathinfo();
  }

  public function method()
  {
    return $this->request->getMethod();
  }

  public function request()
  {
    return $this->request;
  }

  public function __call($method, $parameters)
  {
    return call_user_func_array([$this->request, $method], $parameters);
  }

}
