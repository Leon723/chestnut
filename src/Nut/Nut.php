<?php namespace Chestnut\Nut;

use \Chestnut\Core\Registry;

class Nut implements \Countable, \IteratorAggregate
{

  /**
   * 数据连接
   * @var PDO
   */
  protected $db;

  /**
   * 数据表名
   * @var string
   */
  protected $table;

  /**
   * 模型名
   * @var string
   */
  protected $model;

  /**
   * 是否使用 Nut 默认的时间戳
   * @var boolean
   */
  protected $timeStamp;

  /**
   * 是否为新建对象
   * @var boolean
   */
  protected $isNew;

  /**
   * ORM 数据集
   * @var array
   */
  protected $property = [];

  /**
   * 查询参数
   * @var array
   */
  protected $parameter = [];

  /**
   * 查询条件
   * @var SQLCreater
   */
  protected $sql;

  /**
   * Nut 单一实例
   * @var Nut
   */
  protected static $instance = null;

  /**
   * Nut ORM 构造函数
   */
  public function __construct($isNew = true)
  {
    $className = explode("\\", get_class($this));

    $this->model = array_pop($className);

    if(! isset($this->table))
    {
      $prefix = Registry::get('config')->has("database")
              ? Registry::get('config')->get('database')['mysql']['prefix']
              : "";

      $this->table = $prefix . strtolower(preg_replace("#((?<=[a-z])(?=[A-Z]))#", "_", $this->model)) . 's';
    }

    $this->db = new Database($this->table);
    $this->sql = new SQLCreater();
    $this->sql->set('table', $this->table);
    $this->isNew = $isNew;
  }

  /**
   * 静态创建 Nut 实例
   * @return Nut
   */
  private static function _make()
  {
    if(static::$instance === null) {
      return static::$instance = new static(false);
    }

    return static::$instance;
  }

  /**
   * 设置 SQL 参数
   * 可传入一个 键名为参数名以及键值为参数值 的数组，快速定义多个参数
   * @param string $name  参数名
   * @param string $value 参数值
   */
  public function setParameter($name, $value = null)
  {
    if($value !== null) {
      $this->parameter[$name] = $value;
      return;
    }

    $this->parameter = $name;
  }

  /**
   * 添加 SQL 参数
   * @param  string $name  参数名
   * @param  string $value 参数值
   */
  public function appendParameter($name, $value)
  {
    $this->setParameter($name, $value);
  }

  /**
   * 是否为新建的对象
   * @return boolean
   */
  public function isNew()
  {
    return $this->isNew;
  }

  /**
   * 获取查询数据
   * @return stdClass 查询数据
   */
  public function get()
  {
    $sql = $this->sql->createSelect();

    $this->db->query($sql);
    $this->db->execute($this->parameter);

    $this->property = $this->db->fetch(\PDO::FETCH_OBJ);

    return $this;
  }

  /**
   * 保存改变
   */
  public function save()
  {
    if($this->isNew()) {
      $sql = $this->sql->createInsert(array_keys((array) $this->property));
    } else {
      $sql = $this->sql->createUpdate(array_keys((array) $this->property));
    }

    $this->db->query($sql);
    $this->db->execute((array) $this->property);

    $this->property->id = $this->db->lastInsertId();
  }

  /**
   * 获取所有查询数据集
   * @param  string $type 获取类型，默认为stdClass
   */
  public function fetchAll($type = null)
  {
    switch($type) {
      case "array":
        $type = \PDO::FETCH_ASSOC;
        break;
      default:
        $type = \PDO::FETCH_OBJ;
        break;
    }

    $this->property = $this->db->fetchAll($type);
  }

  /**
   * 获取第一条数据集
   * @param  string $type 获取类型，默认为stdClass
   */
  public static function first($type = null)
  {
    $obj = static::_make();
    $obj->_select("*");

    $sql = $obj->sql->createSelect();

    $obj->db->query($sql);
    $obj->db->execute();

    $obj->fetchAll($type);

    $obj->property = $obj->property[0];

    return $obj;
  }

  /**
   * 查找所有数据集
   */
  public static function findAll()
  {
    $obj = static::_make();
    $obj->_select("*");

    $sql = $obj->sql->createSelect();

    $obj->db->query($sql);
    $obj->db->execute();

    $obj->fetchAll();

    return $obj;
  }

  /**
   * 删除数据
   * @param  integer $id 数据ID
   */
  private function _delete($id = null)
  {
    if($id === null) {
      $id = $this->property->id;
    }

    $this->_where("id", $id);

    $sql = $this->sql->createDelete();

    $this->db->query($sql);
    $this->db->execute($this->parameter);
    $this->property = [];
  }

  /**
   * 设置查询列名
   * @param  string $select 列名
   * @param  string $alias  别名
   */
  private function _select($select, $alias = null)
  {
    if(is_array($select)) {
      foreach($select as $key=> $value) {
        $this->select($key, $value);
      }
    }

    if(is_integer($select) && $alias !== null) {
      $select = $alias;
      $alias = null;
    } else if($alias !== null) {
      $alias = "AS $alias";
    }

    $this->sql->set("select", [$select=> $alias]);

    return $this;
  }

  /**
   * 设置 where 条件
   * @param  string $where  where 列名
   * @param  string $symbol 运算符
   * @param  string $value  列值
   * @param  string $link   连接方式，默认And
   */
  private function _where($where, $symbol, $value = null, $link = null)
  {
    if($value === null && ! in_array(strtoupper($symbol), ["＝", "<>", "<", ">", ">=", "<=", "BETWEEN", "LIKE", "IN"]))
    {
      $link = $value;
      $value = $symbol;
      $symbol = "=";
    }

    if($link === null) {
      $link = "AND";
    }
    if((int) $this->sql->sizeof("where") === 0) {
      $this->sql->set("where", ["WHERE $where"=> "$symbol :$where"]);
    } else {
      $this->sql->set("where", ["$link $where"=> "$symbol :$where"]);
    }

    if(strtoupper($symbol) === "LIKE") {
      $value = "%$value%";
    }

    $this->appendParameter(":$where", $value);

    return $this;
  }

  /**
   * 设置 ORDER 条件
   * @param  string $order 排序列名
   * @param  boolean $desc  是否逆序排列
   */
  private function _order($order, $desc = false)
  {
    if($desc) {
      $order .= " DESC";
    }

    $this->sql->set("order", "ORDER BY $order");

    return $this;
  }

  /**
   * 设置 Limit 条件
   * @param  integer  $limit  限制值
   * @param  integer $offset 偏移值
   */
  private function _limit($limit, $offset = 0)
  {
    $this->sql->set("limit", "LIMIT $offset, $limit");
    return $this;
  }

  public function __call($method, $parameters)
  {
    if(in_array($method, ['select', 'where', 'order', 'limit', 'delete'])) {
      return call_user_func_array([$this, "_$method"], $parameters);
    }
  }

  public static function __callStatic($method, $parameters)
  {
    $obj = static::_make();

    if(in_array($method, ['select', 'where', 'order', 'limit', 'delete'])) {
      return call_user_func_array([$obj, "_$method"], $parameters);
    }
  }

  public function __get($key)
  {
    if(is_array($this->property)) {
      return $this->property[$key];
    } else {
      return $this->property->$key;
    }
  }

  public function __set($key, $value)
  {
    if(is_array($this->property)) {
      $this->property[$key] = $value;
    } else {
      $this->property->$key = $value;
    }
  }

  public function count()
  {
    return $this->db->count();
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->property);
  }
}
