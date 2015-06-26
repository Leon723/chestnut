<?php
namespace Cheatnut;

class Cheatnut {
  
  protected $config;

  public function __construct(Array $config = null) {
    $whoops = new \Whoops\Run();
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
    
    if(!is_array($config)) {
      $config = require_once("config/config.php");
    }

    $this->config = $config;
    
    $this->init();
  }
  
  protected function init() {
    $this->alias();
  }
  
  protected function alias() {
    if(! is_array($this->config['alias'])) {
      throw new \Exception("Configure [ alias ] is not an Array, please check");
    }
    
    foreach($this->config['alias'] as $alias => $class) {
      class_alias($class, $alias);
    }
  }

  public function run() {
    $request = new Request\Request();

    Route\Router::match($request);

    if(! Route\Router::current()) {
      throw new \Exception("Page Not Found", 404);
    }

    Route\Router::dispatch();
  }
}
