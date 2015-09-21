<?php namespace Chestnut\Core\Bootstrap;

use Chestnut\Application;

class Bootstrap
{
  protected $app;

  protected $bootstraps = [
    'Chestnut\Core\Bootstrap\LoadConfigure',
    'Chestnut\Core\Bootstrap\RegisterProviders',
    // 'Chestnut\Core\Bootstrap\RegisterAliases',
    'Chestnut\Core\Bootstrap\RegisterStaticizer',
    'Chestnut\Core\Bootstrap\BootProviders'
  ];

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function bootstrap()
  {
    foreach($this->bootstraps as $bootstrap) {
      (new $bootstrap($this->app))->bootstrap();
    }
  }
}
