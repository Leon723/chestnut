<?php
namespace Cheatnut;

use Cheatnut\Http\Request;
use Cheatnut\Http\Response;
use Cheatnut\Http\Router;

class Cheatnut {

  protected $container;

  protected $middleware;

  protected $root;

  public function __construct(Array $config = null) {

    $this->settings = $config;
    $this->defaultSettings();

    $this->container = new \Cheatnut\Core\Container();

    $this->container->singleton('environment', function($c) {
      return \Cheatnut\Core\Environment::getInstance();
    });

    $this->container->singleton('request', function($c) {
      return new \Cheatnut\Http\Request($c['environment']);
    });

    $this->container->singleton('response', function($c) {
      return new \Cheatnut\Http\Response();
    });

    $this->container->singleton('route', function($c) {
      return new \Cheatnut\Http\Router($c);
    });

    $this->container->singleton('view', function($c) {
      return new \Cheatnut\View\View($this->root() . DIRECTORY_SEPARATOR . '../app/views/', $this->root() . DIRECTORY_SEPARATOR . 'views/');
    });

    $this->middleware = [$this];
    // $this->add(new Middleware\Resources);
  }

  public function defaultSettings()
  {
    if(isset($this->settings['timezone']))
    {
      date_default_timezone_set($this->settings['timezone']);
    }
  }

  public function add(\Cheatnut\Core\Middleware $newMiddleware)
  {
    if(in_array($newMiddleware, $this->middleware))
    {
      $middleware_class = get_class($newMiddleware);
      throw new \RuntimeException("Circular Middleware setup detected. Tried to queue the same Middleware instance ({$middleware_class}) twice.");
    }

    $newMiddleware->setApplication($this);
    $newMiddleware->setNextMiddleware($this->middleware[0]);

    array_unshift($this->middleware, $newMiddleware);
  }

  public function root()
  {
    return rtrim($_SERVER['DOCUMENT_ROOT'], '/');
  }

  // protected function init() {
  //   $this->alias();
  // }

  // protected function alias() {
  //   if(! is_array($this->config['alias'])) {
  //     throw new \Exception("Configure [ alias ] is not an Array, please check");
  //   }

  //   foreach($this->config['alias'] as $alias => $class) {
  //     class_alias($class, $alias);
  //   }
  // }

  public function __get($key)
  {
    return $this->container->get($key);
  }

  public function get()
  {
    $args = func_get_args();

    $this->route->get($args);
  }

  public function post()
  {
    $args = func_get_args();

    $this->route->get($args);
  }

  public function put()
  {
    $args = func_get_args();

    $this->route->get($args);
  }

  public function delete()
  {
    $args = func_get_args();

    $this->route->get($args);
  }

  public function patch()
  {
    $args = func_get_args();

    $this->route->get($args);
  }

  public function any()
  {
    $args = func_get_args();

    $this->route->get($args);
  }

  public function notFound()
  {
    $this->response->setStatus(404);
    $this->response->setContent(Http\Response::getMessageForCode(404));
  }

  public function run() {
    $this->middleware[0]->call();

    list($status, $header, $content) = $this->response->finalize();

    if(headers_sent() === false)
    {
      if(strpos(PHP_SAPI, 'cgi') === 0) {
        header(sprintf('Status: %s', Http\Response::getMessageForCode($status)));
      } else {
        header(sprintf('HTTP/1.1 %s', Http\Response::getMessageForCode($status)));
      }

      foreach($header as $name => $value) {
        header("$name: $value");
      }
    }

    if(! $this->request->isHead())
    {
      echo 1;
      // echo $content;
    }
  }

  public function call()
  {
    ob_start();

    if($this->route->match($this->request->getMethod(), $this->request->getUri()))
    {
      $this->route->current()->dispatch();
      $this->response->setContent(ob_get_clean());
    }
    else
    {
      $this->notFound();
    }
  }
}
