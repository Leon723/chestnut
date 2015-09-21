<?php namespace Chestnut\Core\Nut;

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
   */
  protected $className;

  /**
   * 是否使用 Nut 默认的时间戳
   * @var boolean
   */
  protected $timeStamp;

  /**
   * 是否为新建对象
   * @var boolean
   */
  protected $exists;

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
   * Nut 实例数组
   * @var Nut
   */
  protected $instances = [];

  /**
   * Nut ORM 构造函数
   */
  public function __construct($className, $exists = false)
  {
    $this->className = $className;
    $className = explode("\\", $className);

    if(! isset($this->table))
    {
      $prefix = config('database.mysql.prefix', '');

      $this->table = $prefix . strtolower(preg_replace("#((?<=[a-z])(?=[A-Z]))#", "_", array_pop($className))) . 's';
    }

    $this->db = new Database($this->table);
    $this->sql = new SQLCreater();
    $this->sql->set('table', $this->table);
    $this->exists = $exists;
  }

  public static function register($app)
  {
    $app->register(static::class);
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
    return $this->exists;
  }

  /**
   * 执行查询
   * @param  string $sql 查询语句
   */
  public function execute($sql)
  {
    $this->db->query($sql);
    $this->db->execute($this->parameter);
    if($err = $this->db->getError()) {
      switch($err->code) {
        case 42:
          if(method_exists($this->className, 'create')) {
            $this->db->clearError();
            $tm = new TableManager($this->table);
            call_user_func([$this->className, 'create'], $tm);

            $this->execute($tm->create());
            $this->execute($sql);
          } else {
            throw new \PDOException($err->message, $err->code);
          }
          break;
        default:
          throw new \PDOException($err->message, $err->code);
      }
    }
  }

  /**
   * 获取所有查询数据集
   * @param  string $type 获取类型，默认为stdClass
   */
  public function fetch($type = null)
  {
    switch($type) {
      case "array":
        $type = \PDO::FETCH_ASSOC;
        break;
      default:
        $type = \PDO::FETCH_OBJ;
        break;
    }

    if($this->db->count() > 1) {
      $property = $this->db->fetchAll($type);
    } else {
      $property = $this->db->fetch($type);
    }

    $this->property = $property;
  }

  /**
   * 获取查询数据
   * @return stdClass 查询数据
   */
  public function get($type = null)
  {
    $sql = $this->sql->createSelect();

    $this->execute($sql);

    $this->fetch($type);

    return $this;
  }


  /**
   * 保存更改
   */
  public function save()
  {
    if(! $this->isNew()) {
      $sql = $this->sql->createInsert(array_keys((array) $this->property));
    } else {
      $sql = $this->sql->createUpdate(array_keys((array) $this->property));
    }

    $this->db->query($sql);
    $this->db->execute((array) $this->property);

    if($err = $this->db->getError()) {
      throw new \PDOException($err->message, $err->code);
    }

    $this->id = $this->db->lastInsertId();
  }


  /**
   * 获取第一条数据集
   * @param  string $type 获取类型，默认为stdClass
   */
  public function first($type = null)
  {
    $this->select("*");

    $sql = $this->sql->createSelect();

    $this->execute($sql);
    $this->fetch();

    return $this;
  }

  /**
   * 获取指定 ID 的数据
   * @param  integer $id   指定数据的 ID
   * @param  PDOFETCTType $type 指定的返回类型，默认为stdCalss
   * @return Nut
   */
  public function findOne($id, $type = null)
  {
    $this->select("*")
         ->where("id", $id);

    $sql = $this->sql->createSelect();

    $this->execute($sql);
    $this->fetch($type);

    return $this;
  }

  /**
   * 查找所有数据集
   */
  public function findAll($type = null)
  {
    $this->select("*");

    $sql = $this->sql->createSelect();

    $this->execute($sql);
    $this->fetch($type);

    return $this;
  }

  /**
   * 删除数据
   * @param  integer $id 数据ID
   */
  public function delete($id = null)
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
  public function select($select, $alias = null)
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
  public function where($where, $symbol, $value = null, $link = null)
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
  public function order($order, $desc = false)
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
  public function limit($limit, $offset = 0)
  {
    $this->sql->set("limit", "LIMIT $offset, $limit");
    return $this;
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
    return count($this->property);
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->property);
  }
}
