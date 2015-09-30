<?php namespace Chestnut\Application;

use Chestnut\Http\Response;

class Dispatcher
{
  protected $app;

  public function __construct(Application $app)
  {
    $this->app = $app;
  }

  public function dispatch()
  {
    $app = $this->app;

    $app->route->match();

    if(! $app->isDispatchable()) {
      $app->response->setStatusCode(Response::HTTP_NOT_FOUND);
      $app->response->setContent('<head><title>页面不存在</title></head><body style="background:#333;"><div style="position:absolute; top:50%;left:50%;transform:translate(-50%,-50%);color:#999;font-size:36pt">您访问的页面不在地球上</div></body>');

      return;
    }

    try{
      ob_start();
      $object = $app->current->dispatch($app);
      $result = ob_get_clean();

      if($app->response->isRedirection()) {
        return;
      }

      if(strlen($result) === 0) {
        $app->response->setContent($object);
      } else {
        $app->response->setContent($result);
      }

      $app->response->prepare($app->request);

      return;
    } catch(\Exception $e) {
      if($app->config->get('debug')) {
        throw new \Exception($e);
      }
    }

  }
}
