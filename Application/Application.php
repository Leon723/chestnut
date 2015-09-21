<?php namespace Chestnut\Application;

use Chestnut\Support\Parameter;
use Chestnut\Support\Component;

class Application extends Component
{
  protected static $instance;

  protected $basePath;

  protected $middleware;

  public function __construct($basePath)
  {
    
  }
}
