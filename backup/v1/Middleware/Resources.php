<?php namespace Cheatnut\Middleware;
  
  class Resources extends \Cheatnut\Core\Middleware
  {
    protected $mimeType = [
      'css'=> 'text/css',
      'js'=> 'text/javascript'
    ];
    
    public function call()
    {
      preg_match('#\.(\w+)$#', $this->app->request['PATH_INFO'], $extension);
      
      if(! is_array($extension) && isset($this->mimeType[$extension[1]]))
      {
        $this->app->request['mimeType'] = $this->mimeType[$extension[1]];
      }
      
      $this->next->call();
    }
  }