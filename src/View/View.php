<?php namespace Chestnut\Core\View;

use Chestnut\Application;

class View
{
  protected $app;
  protected $properties;
  protected $fileName;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function setFile($filename)
  {
    $this->fileName = $filename;

    return $this;
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

  public function getCachePath()
  {
    return $this->app['path'] . $this->app['config.view.cache'] . md5($this->fileName);
  }

  public function getTemplatesPath()
  {
    $this->app['path'] . $this->app['config.view.templates'] . $this->fileName . ".php";
  }

  public function checkCache()
  {
    if(! file_exists($this->getCachePath())) {
      return false;
    }

    if(filemtime($this->getCachePath()) < filemtime($this->getTemplatesPath())) {
      return false;
    }

    return false;
  }

  public function template()
  {
    $engine = new TemplateEngine($this->getTemplatesPath());

    $content = $engine->make();

    if(! file_put_contents($this->getCachePath(), $content)) {
      throw new \Exception("编译模板文件出错");
    }
  }

  public function display()
  {
    extract($this->properties);

    if(! $this->checkCache()) {
      $this->template();
    }

    require $this->getCachePath();
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
