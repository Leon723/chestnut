<?php namespace Chestnut\Nut;

class Nut
{
  protected $db;
  protected static $instance = null;

  public function __construct($staticName = null)
  {
    if($staticName != null) {
      $className = $staticName;
    } else {
      $className = get_class($this);
    }

    $className = explode('\\', $className);

    $this->db = new Database(array_pop($className));
  }

  private static function _make()
  {
    if(static::$instance === null) {
      return static::$instance = new self(get_called_class());
    }

    return static::$instance;
  }

  public static function select($select, $value = null)
  {
    $obj = static::_make();

    return $obj->db->select($select, $value);
  }

  public static function where($where, $symbol, $value = null, $link = "AND")
  {
    $obj = static::_make();

    return $obj->db->where($where, $symbol, $value, $link);
  }

  public static function order($order, $desc = false)
  {
    $obj = static::_make();

    return $obj->db->order($order, $desc);
  }

  public static function limit($limit, $offset = 0)
  {
    $obj = static::_make();

    return $obj->db->limit($limit, $offset);
  }

  public static function findAll($type = null)
  {
    $obj = static::_make();

    return $obj->db->get($type);
  }

  public static function findOne($id = null, $type = null)
  {
    $obj = static::_make();

    if($id !== null) {
      $obj->db->where("id", $id);
    }

    return $obj->db->first($type);
  }

  public function save()
  {
    $this->db->save();
  }

  public function __get($name)
  {
    if(array_key_exists($this->has($name))) {
      return $this->db->get($name);
    }

    throw new \RuntimeException("Property [$name] not define in " . get_class($this) . ",please check");
  }

  public function __set($name, $value)
  {
    $this->db->set($name, $value);
  }
}
