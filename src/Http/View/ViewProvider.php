<?php namespace Cheatnut\Http\View;

class ViewProvider
{
  public static function make($path, $data = [])
  {
    $view = new View();

    $view->setFile($path)->data($data);
    
    return $view;
  }
}
