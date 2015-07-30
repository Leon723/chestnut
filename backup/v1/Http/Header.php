<?php namespace Cheatnut\Http;
  
class Header extends \Cheatnut\Core\Container
{
  protected static $special = [
    'CONTENT_TYPE',
    'CONTENT_LENGTH'
  ];
  
  public function __construct($header)
  {
    $this->replace($header);
  }
  
  public static function extra($header)
  {
    $result = [];
    foreach($header as $key => $value) {
      if(strpos($key, 'HTTP_') === 0 || strpos($key, 'X_') === 0 || in_array($key, self::$special)) {
        if($key === "CONTENT_LENGTH") continue;
        
        $result[$key] = $value;
      }
    }
    
    return $result;
  }
}