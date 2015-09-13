<?php namespace Chestnut\Staticizer;

use Countable;
use IteratorAggregate;

class Nut extends Staticizer implements Countable, IteratorAggregate
{
  protected $nut;

  public function __construct()
  {
    $this->nut = app('nut', ['className'=> get_called_class(), 'exists'=> false]);
  }

  public static function __callStatic($method, $parameters)
  {
    return call_user_func_array([app('nut', ['className'=> get_called_class(), 'exists'=> true]), $method], $parameters);
  }

  public function __call($method, $parameters)
  {
    return call_user_func_array([$this->nut, $method], $parameters);
  }

  public function __get($key)
  {
    $this->nut->$key;
  }

  public function __set($key, $value)
  {
    $this->nut->$key = $value;
  }

  public function count()
  {
    return count($this->nut);
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->nut);
  }
}
