<?php namespace Chestnut\Http;

use Chestnut\View\View;
use Chestnut\Http\Request;
use Chestnut\Support\Parameter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response extends SymfonyResponse
{
  public function redirect($url, $status = 302, $headers = [])
  {
    $this->setStatusCode($status);
    $this->headers->set('Location', $url);
  }
}
