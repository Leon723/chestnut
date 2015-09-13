<?php namespace Chestnut\Core\Bootstrap;

use Chestnut\Application;

class BootProviders
{
  protected $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function bootstrap()
  {
    $providers = $this->app->getRegisterProviders();

    foreach($providers as $provider) {
      $this->app->make($provider);
    }
  }
}
