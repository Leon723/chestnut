<?php namespace Chestnut\Core\Bootstrap;

use Chestnut\Application;

class RegisterProviders
{
  protected $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function bootstrap()
  {
    $providers = $this->app->config->get('app.providers',[
        'Chestnut\Core\Request\Request',
        'Chestnut\Core\Response\Response',
        'Chestnut\Core\Route\RouteProvider',
        'Chestnut\Core\View\View',
        'Chestnut\Core\Nut\Nut',
    ]);

    $this->registerProviders($providers);
  }

  public function registerProviders($providers)
  {
    foreach($providers as $provider) {
      $this->app->registerProvider($provider);
    }
  }

}
