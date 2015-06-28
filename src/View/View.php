<?php
  namespace Cheatnut\View;
  
class View {
  private static $instance = null;
  protected $path;
  protected $data = [];
  
  private function __construct(Array $config) {
    if(is_array($config)){
      foreach($config as $config => $value) {
        $this->$config = $value;
      }
    }
  }
  
  public static function getInstance($path) {
    if(self::$instance === null && ! is_object(self::$instance)) {
      self::$instance = new self($path);
    }
    
    return self::$instance;
  }
  
  public function init($viewPath) {
    $this->file = $viewPath;
    return $this;
  }
  
  public function data(Array $data) {
    $this->data = $data;
    return $this;
  }
  
  public function __call($key, $params) {
    $this->data[$key] = $params[0];
    return $this;
  }
  
  public function display() {
    extract($this->data);
    
    require_once $this->path . ".php";
  }
}