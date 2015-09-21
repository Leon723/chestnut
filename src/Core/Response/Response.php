<?php namespace Chestnut\Core\Response;

use Chestnut\Application;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response
{
  protected $app;

  protected $response;

  public function __construct(Application $app)
  {
    $this->app = $app;

    $this->response = new SymfonyResponse();
  }

  public static function register($app)
  {
    $app->singleton(static::class);
  }

  public function __call($method, $parameters)
  {
    return call_user_func_array([$this->response, $method], $parameters);
  }
}
