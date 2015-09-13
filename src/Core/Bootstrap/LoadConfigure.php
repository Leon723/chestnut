<?php namespace Chestnut\Core\Bootstrap;

use Chestnut\Application;

class LoadConfigure
{
  protected $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function bootstrap()
  {
    $this->app->singleton('Chestnut\Core\Config\Config');

    foreach($this->getConfigures() as $name=> $configPath) {
      $this->app->config->set($name, require $configPath);
    }
  }

  public function getConfigures()
  {
    $configHandler = opendir($this->app['path.config']);
    $files = [];

    while(false !== $file = readdir($configHandler)) {
      if(preg_match("/([\s\S]*).php$/", $file, $m)) {
        $files[$m[1]] = $this->app['path.config'] . DIRECTORY_SEPARATOR . $m[0];
      }
    }

    return $files;
  }
}
