<?php namespace Chestnut\Http\Response;

class ResponseProvider
{
  protected $response;

  public function __construct()
  {
    $this->response = new Response();
  }

  public function notFound()
  {
    $this->response->setStatus('404');
    $this->response->setContent('蛤蛤，找不到页面！');
  }

  public function finalize()
  {
    if(in_array($this->response->getStatus(), [204, 304]))
    {
      unset($this->response->header['Content-Type']);
      unset($this->response->header['Content-Length']);
      $this->response->setContent('');
    }

    return [$this->response->getStatus(), $this->response->headers(), $this->response->getContent()];
  }

  public function setContent($content, $replace = false)
  {
    $this->response->setContent($content, $replace);
  }


}
