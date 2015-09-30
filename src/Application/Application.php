<?php namespace Chestnut\Application;

use Chestnut\Support\Parameter;
use Chestnut\Support\Component;
use Chestnut\Http\Request;

class Application extends Component
{
  /**
   * 程序中间件
   *
   * @var array
   */
  protected $middleware = [];

  public function __construct($basePath = null, $config = [])
  {
    parent::__construct();
    static::setInstance($this);
    $this->registerStaticizer();

    $this->singleton(['Chestnut\Http\Request'=> 'request'], function() {
      return Request::createFromGlobals();
    });
    $this->singleton(['Chestnut\Http\Router'=> 'route']);
    $this->singleton(['Chestnut\Http\Response'=> 'response']);
    $this->register(['Chestnut\Application\Dispatcher'=> 'dispatch']);

    $this->instance('app', $this);
    $this->instance('Chestnut\Application\Application', $this);
    $this->instance('config', new Parameter(array_merge($this->getDefaultConfig(), $config)));
    $this->instance('path', $basePath);

    array_unshift($this->middleware, $this);
  }

  public function isDispatchable()
  {
    return $this->resolved('current');
  }

  public function getDefaultConfig()
  {
    return [
      'timezone'=> 'Asia/Chongqing',
      'debug'=> true,
      'view'=> [
        'templates' => '/app/views/',
        'cache' => '/public/views/'
      ],
    ];
  }

  public function registerStaticizer()
  {
    foreach(['Nut', 'Route', 'View'] as $staticizer) {
      class_alias("Chestnut\Staticizer\\$staticizer", $staticizer, true);
    }
  }

  public function boot()
  {
    $this->callMiddleware();
  }

  public function call()
  {
    if($this->config->has('timezone')) {
      date_default_timezone_set($this->config->get('timezone'));
    }

    $this->dispatch->dispatch();

    $this->response->send();
  }

  public function callMiddleware()
  {
    while(count($this->middleware) >0) {
      $middleware = array_shift($this->middleware);

      $middleware->call();
    }
  }
}
