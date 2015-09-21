<?php namespace Chestnut;

use Chestnut\Core\Component\Component;
use Symfony\Component\HttpFoundation\Response;

class Application extends Component
{
  /**
   * 根路径
   *
   * @var string
   */
  protected $basePath;

  /**
   * 服务提供者注册存储
   *
   * @var array
   */
  protected $registerProviders = [];

  /**
   * 框架版本号
   *
   * @var string
   */
  const VERSION = 'Chestnut v0.4.0';

  /**
   * 创建新的框架
   *
   * @param string $basePath 根路径
   */
  public function __construct($basePath = null)
  {
    $this->registerBaseComponent();
    $this->registerCoreAlias();
    $this->setPath($basePath);
  }

  public function version()
  {
    return static::VERSION;
  }

  /**
   * 设置路径
   *
   * @param string $basePath 根路径
   * @return void
   */
  public function setPath($basePath)
  {
    if(! is_null($basePath)) {
      $this->basePath = $basePath;
      $this->bindPath();
    }
  }

  /**
   * 绑定路径
   *
   * @return void
   */
  public function bindPath()
  {
    $this->instance('path', $this->basePath() . DIRECTORY_SEPARATOR . 'app');

    foreach(['config', 'public', 'resource', 'cache'] as $key) {
      $this->instance('path.' . $key, $this->{$key . 'Path'}());
    }
  }

  public function basePath()
  {
    return $this->basePath;
  }

  /**
   * 配置文件夹路径
   *
   * @return string 配置文件夹
   */
  public function configPath()
  {
    return $this->basePath() . DIRECTORY_SEPARATOR . 'config';
  }

  /**
   * 公开文件夹路径
   *
   * @return string 公开文件夹
   */
  public function publicPath()
  {
    return $this->basePath() . DIRECTORY_SEPARATOR . 'public';
  }

  /**
   * 资源文件夹
   *
   * @return string 资源文件夹
   */
  public function resourcePath()
  {
    return $this->basePath() . DIRECTORY_SEPARATOR . 'resource';
  }

  /**
   * 缓存文件夹
   *
   * @return string 缓存文件夹
   */
  public function cachePath()
  {
    return $this->basePath() . DIRECTORY_SEPARATOR . 'cache';
  }

  /**
   * 注册基础组件
   *
   * @return void
   */
  public function registerBaseComponent()
  {
    static::setInstance($this);

    $this->instance('app', $this);
    $this->instance('Chestnut\Application', $this);
    $this->singleton('Chestnut\Core\Bootstrap\Bootstrap');
  }

  /**
   * 注册核心别名
   *
   * @return void
   */
  public function registerCoreAlias()
  {
    $aliases = [
      'bootstrap'=> 'Chestnut\Core\Bootstrap\Bootstrap',
      'config'=> 'Chestnut\Core\Config\Config',
      'request'=> 'Chestnut\Core\Request\Request',
      'response'=> 'Chestnut\Core\Response\Response',
      'route'=> 'Chestnut\Core\Route\RouteProvider',
      'view'=> 'Chestnut\Core\View\View',
      'nut'=> 'Chestnut\Core\Nut\Nut',
    ];

    foreach($aliases as $alias=> $component) {
      $this->alias($alias, $component);
    }
  }

  /**
   * 注册服务
   *
   * @return void
   */
  public function registerProvider($provider)
  {
    $this->registerProviders[] = is_string($provider) ? $provider : get_class($provider);

    $provider::register($this);
  }

  /**
   * 	获取注册的服务
   *
   * @return array
   */
  public function getRegisterProviders()
  {
    return $this->registerProviders;
  }

  public function notFound()
  {
    $this->response->setContent('<body style="background:#333;"><div style="position:absolute; top:50%;left:50%;transform:translate(-50%,-50%);color:#999;font-size:36pt">您要找的页面不在地球上</div></body>');
    $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
  }

  /**
   * 启动程序
   *
   * @return void
   */
  public function boot()
  {
    $this['bootstrap']->bootstrap();
  }

  public function terminate()
  {
    $this->route->matches();

    if(! $this->route->dispatchable()) {
      $this->notFound();
    } else {
      $result = $this->route->dispatch();

      $this->response->setContent($result);
    }

    $this->response->send();
  }
}
