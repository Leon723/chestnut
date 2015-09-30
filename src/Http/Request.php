<?php namespace Chestnut\Http;

use Chestnut\Support\Parameter;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest
{
  public function method()
  {
    return $this->getMethod();
  }

  public function path()
  {
    return $this->getPathinfo();
  }
}
