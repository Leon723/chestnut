<?php namespace Chestnut\Core;

  class Application
  {
    protected $config;

    public function __construct($config = [])
    {
      Registry::register("config", function($defaults) use($config) {
        return new \Chestnut\Core\Config($defaults, $config);
      });

      $this->__initialize();
    }

    private function __initialize()
    {
      $this->config = Registry::get('config', [
        'alias'=> [
          'Route'=> '\Chestnut\Http\Route\RouteProvider',
          'App\Controllers\Registry'=> '\Chestnut\Core\Registry',
          'App\Controllers\Request'=> '\Chestnut\Http\Request\RequestProvider',
          'App\Controllers\Controller'=> '\Chestnut\Http\Controller',
          'App\Controllers\View'=> '\Chestnut\Http\View\ViewProvider',
          'App\Models\Nut' => '\Chestnut\Nut\Nut',
        ],
        'registry'=> [
          'request'=> function() {
            return \Chestnut\Http\Request\RequestProvider::getInstantce();
          },
          'route'=> function() {
            return new \Chestnut\Http\Route\RouteProvider();
          },
          'response'=> function() {
            return new \Chestnut\Http\Response\ResponseProvider();
          }
        ]
      ]);

      if($this->config->has('timezone')) {
        date_default_timezone_set($this->config['timezone']);
      }

      $this->__registerAlias();
      $this->__registerRegistry();
    }

    private function __registerAlias()
    {
      if(! $this->config->has('alias')) {
        return false;
      }

      foreach($this->config['alias'] as $name => $class) {
        class_alias($class, $name);
      }
    }

    private function __registerRegistry()
    {
      if(! $this->config->has('registry')) {
        return false;
      }

      foreach($this->config['registry'] as $name => $callable) {
        Registry::register($name, $callable);
      }
    }

    public function run()
    {
      $route = Registry::get('route');
      $request = Registry::get('request');
      $response = Registry::make('response');

      $this->config['root'] = $request->getRoot() . DIRECTORY_SEPARATOR;

      $route->match($request->getMethod(), $request->getUrl());

      if($route->current() !== null)
      {
        $result = $route->dispatch();
        $response->setContent($result);
      }
      else {
        $response->notFound();
      }

      list($status, $header, $content) = $response->finalize();

      if(headers_sent() === false)
      {
        if(strpos(PHP_SAPI, 'cgi') === 0) {
          header(sprintf('Status: %s', \Chestnut\Http\Response\Response::getMessageForCode($status)));
        } else {
          header(sprintf('HTTP/1.1 %s', \Chestnut\Http\Response\Response::getMessageForCode($status)));
        }

        foreach($header as $name => $value) {
          header("$name: $value");
        }
      }

      if(! $request->isHead()) {
        echo $content;
      }
    }
  }
