<?php namespace Chestnut\Core\Nut;

class TableManager
{
  protected $property;

  public function __construct($table)
  {
    $this->table = $table;
    $this->engine('INNODB');
    $this->charset("UTF8");
  }

  public function increment($name)
  {
    $this->set($name, [
      "type"=> "INT",
      "nullable"=> "NOT NULL",
      "increment"=> "AUTO_INCREMENT"
    ]);

    $this->primary("id");
  }

  public function primary($name)
  {
    $this->set('primary', $name);
  }

  public function unique($name)
  {
    if(is_string($name)) {
      $name = func_get_args();
    }

    foreach($name as $key) {
      $this->property['unique'][] = $key;
    }
  }

  public function engine($engine)
  {
    $this->set('engine', $engine);
  }

  public function charset($charset)
  {
    $this->set('charset', $charset);
  }

  public function incrementIndex($index)
  {
    $this->set('increment', $index);
  }

  public function string($name, $length = 255, $nullable = false)
  {
    $this->set($name, [
      "type"=> "VARCHAR($length)",
      "nullable"=> $nullable ? "NULL" : "NOT NULL"
    ]);
  }

  public function integer($name, $nullable = false)
  {
    $this->set($name, [
      "type"=> "INT",
      "nullable"=> $nullable ? "NULL" : "NOT NULL"
    ]);
  }

  public function tinyinteger($name, $nullable = false)
  {
    $this->set($name, [
      "type"=> "TINYINT",
      "nullable"=> $nullable ? "NULL" : "NOT NULL"
    ]);
  }

  public function text($name, $nullable = true)
  {
    $this->set($name, [
      "type"=> "TEXT",
      "nullable"=> $nullable ? "NULL" : "NOT NULL"
    ]);
  }

  public function timeStamp()
  {
    $this->set("created_at", [
      "type"=> "INT"
    ]);

    $this->set("updated_at", [
      "type"=> "INT"
    ]);

    $this->set("deleted_at", [
      "type"=> "INT"
    ]);
  }

  public function propertyToString($key) {
    return join($this->{$key}, " ");
  }

  public function create()
  {
    $result = "CREATE TABLE " . $this->table
            . "(";

    $parameter = "";

    foreach($this->property as $key=> $value) {
      if(! in_array($key, ['primary', 'unique', 'engine', 'charset', 'table', 'increment'])) {
        $parameter .= "$key " . $this->propertyToString($key) . ",";
      }
    }

    $result .= $parameter . "PRIMARY KEY(" . $this->get("primary") . "),";

    if($this->has('unique')) {
      foreach($this->get('unique') as $key) {
        $result .= "UNIQUE KEY($key),";
      }
    }

    $result = rtrim($result, ",") . ")ENGINE=$this->engine DEFAULT CHARSET=$this->charset";

    if($this->has('increment')) {
      $result .= " AUTO_INCREMENT=" . $this->get('increment');
    }

    return $result;
  }

  public function set($key, $value)
  {
    $this->property[$key] = $value;
  }

  public function get($key)
  {
    return $this->property[$key];
  }

  public function has($key)
  {
    return isset($this->property[$key]);
  }

  public function __get($key)
  {
    return $this->get($key);
  }

  public function __set($key, $value)
  {
    $this->set($key, $value);
  }
}
