<?php namespace Chestnut\Core\View;

use Chestnut\Application;

class View
{
  protected $properties;
  protected $path;
  protected $cache;

  public function __construct(Application $app)
  {
    $this->path = $app['path'] . DIRECTORY_SEPARATOR . 'views/';
    $this->cache = $app->cachePath() . DIRECTORY_SEPARATOR . 'views/';
  }

  public function setFile($filename)
  {
    $this->path .= $filename . '.php';
    $this->cache .= md5($filename);

    return $this;
  }

  public static function register($app)
  {
    $app->register(static::class);
  }

  public function data(array $data)
  {
    if(is_null($this->properties)) {
      $this->properties = $data;
    } else {
      foreach($data as $key=> $item) {
        $this->properties[$key] = $item;
      }
    }

    return $this;
  }

  public function make($filename, $data = [])
  {
    $this->setFile($filename);
    $this->data($data);

    return $this;
  }

  public function checkCache()
  {
    if(! file_exists($this->cache)) {
      return false;
    }

    if(filemtime($this->cache) < filemtime($this->path)) {
      return false;
    }

    return false;
  }

  public function template()
  {
    $engine = new TemplateEngine($this->path);

    $content = $engine->make();

    if(! file_put_contents($this->cache, $content)) {
      throw new \Exception("编译模板文件出错");
    }
  }

  public function display()
  {
    extract($this->properties);

    if(! $this->checkCache()) {
      $this->template();
    }
    
    require $this->cache;
  }

  public function __call($key, $params)
  {
    if(config()->has('caseSensitive') && config('caseSensitive'))
    {
      $this->properties[$key] = $params[0];
    } else {
      $this->properties[strtolower($key)] = $params[0];
    }

    return $this;
  }

}
