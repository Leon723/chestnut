<?php
  namespace Cheatnut\View;

class View {
  protected $path;
  protected $cache;
  protected $content;
  protected $redis;
  protected $data = [];

  public function __construct($path, $cache) {
    $this->path = $path;
    $this->cache = $cache;
  }

  public function data(Array $data = []) {
    $this->data = $data;
    return $this;
  }

  public function setFileName($fileName) {
    $this->path .= $fileName . '.php';
    $this->cache .= md5($fileName) . '.php';
    return $this;
  }

  public function __call($key, $params) {
    $this->data[$key] = $params[0];
    return $this;
  }

  public function checkCache() {
    if(! file_exists($this->cache)){
      return false;
    }

    if($this->redis->get($this->cache) != filemtime($this->path)) {
      return false;
    }

    return true;
  }

  protected function parser() {
    $content = new ViewParser($this->path);

    $this->content = $content->make();
  }

  public function display() {
    extract($this->data);

    if(! $this->checkCache()) {
      $this->parser();

      if(! file_put_contents($this->cache, $this->content)) {
        throw new \Exception("编译模板文件出错");
      }

      $this->redis->set($this->cache, filemtime($this->path));
    }

    ob_start();

    require $this->cache;

    return ob_get_clean();
  }
}
