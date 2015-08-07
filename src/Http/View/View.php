<?php namespace Chestnut\Http\View;

class View
{
  protected $properties;
  protected $path;
  protected $cache;

  public function __construct()
  {
    $c = \Chestnut\Core\Registry::get('config');

    $this->path = $c['root'] . '../app/views/';
    $this->cache = $c['root'] . 'views/';
  }

  public function setFile($filename)
  {
    $this->path .= $filename . '.php';
    $this->cache .= md5($filename);

    return $this;
  }

  public function data(array $data = [])
  {
    $this->properties = $data;

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
    $engine = new \Chestnut\Http\View\TemplateEngine($this->path);

    $content = $engine->make();

    if(! file_put_contents($this->cache, $content)) {
      throw new \Exception("编译模板文件出错");
    }
  }

  public function display()
  {
    extract($this->properties);

    if(! $this->checkCache()) {
      $content = $this->template();
    }

    ob_start();

    require $this->cache;

    return ob_get_clean();
  }

  public function __call($key, $params)
  {
    $c = \Chestnut\Core\Registry::get('config');

    if(array_key_exists('caseSensitive', $c) && $c['caseSensitive'])
    {
      $this->properties[$key] = $params[0];
    } else {
      $this->properties[strtolower($key)] = $params[0];
    }

    return $this;
  }

}
