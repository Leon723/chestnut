<?php namespace Chestnut\Core\Bootstrap;

use Chestnut\Application;

class RegisterStaticizer
{
  protected $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function bootstrap()
  {
    $staticizers= [
      "Request",
      "Response",
      "Route",
      "View",
      "Nut"
    ];

    foreach($staticizers as $staticizer) {
      class_alias("Chestnut\Staticizer\\$staticizer", $staticizer, true);
    }
  }
}
