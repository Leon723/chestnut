<?php namespace Chestnut\Nut;

class Database implements \Countable, \IteratorAggregate
{
  /**
   * $db 数据库连接
   */
  private $db;

  /**
   * $sth 查询声明
   */
  private $sth;

  /**
   * $tableName 数据表名
   */
  private $tableName;

  /**
   * $properties 查询数据存储
   */
  protected $properties;

  /**
   * $parameters 查询参数存储
   */
  protected $parameters;

  /**
   * $_where Where 条件存储
   */
  private $_where;

  /**
   * $_order Order 条件存储
   */
  private $_order;

  /**
   * $_limit Limit 条件存储
   */
  private $_limit;

  /**
   * $_select Select 条件存储
   */
  private $_select;

  /**
   * 构造函数
   * @method __construct
   * @param  string      $className Model 类名
   */

  public function __construct($className)
  {
    $dbc = \Chestnut\Core\Registry::get('config')->get('database');
    $debug = \Chestnut\Core\Registry::get('config')->get('debug');

    try {
      $this->db = new \PDO("mysql:host=" . $dbc['mysql']['host'] . ";dbname=" . $dbc['mysql']['dbname'], $dbc['mysql']['user'], $dbc['mysql']['password'],[\PDO::MYSQL_ATTR_INIT_COMMAND=> "SET NAMES 'utf8';"]);
      $this->db->setAttribute(\PDO::ATTR_ERRMODE, $debug ? \PDO::ERRMODE_EXCEPTION : \PDO::ERRMODE_SILENT);
      $this->tableName = $dbc['mysql']['prefix'] . strtolower(preg_replace("#((?<=[a-z])(?=[A-Z]))#", "_", $className)) . 's';
      $this->properties = [];
      $this->_select = [];
      $this->parameters = [];
    }
    catch(\PDOException $e) {
      echo $e->getMessage();
    }
  }

  /**
   * 设置 SQL 参数
   * @method setParameters
   * @param  array        $params Parameters
   */

  public function setParameters($params)
  {
    $this->parameters = $params;
  }

  /**
   * 添加 SQL 参数
   * @method appendParameters
   * @param  string           $key   参数名
   * @param  string           $value 参数值
   * @return null
   */

  public function appendParameters($key, $value)
  {
    $this->parameters = array_merge($this->parameters, [$key=> $value]);
  }

  /**
   * 设置 Select 项
   * @method select
   * @param  $select 可是用字符串或数组。使用数组，则键名为选择项，键值为别名；是用字符串，则此项为选择项。
   * @param  $value  第一项使用字符串格式时，可传此值作为指定别名。
   * @return Database        返回当前对象
   */

  public function select($select, $value = null)
  {
    if(is_array($select)) {
      foreach($select as $key=> $value) {
        if(is_integer($key)) {
          $this->_select[$value] = null;
        } else {
          $this->_select[$key] = "AS $value";
        }
      }
    } else {
      array_merge($this->_select, [$select=> "AS $value"]);
    }

    return $this;
  }

  public function where($where, $symbol, $value = null, $link = "AND")
  {
    $result = "";

    if(in_array(strtoupper($symbol), ["＝", "<>", "<", ">", ">=", "<=", "BETWEEN", "LIKE"]))
    {
      $result .= "$where ". strtoupper($symbol) . " :$where";
    } else {
      $result .= "$where = :$where";
      $value = $symbol;
    }

    if(! isset($this->_where)) {
      $this->_where = " WHERE " . $result;
    } else {
      $this->_where .= " $link " . $result;
    }

    if(strtoupper($symbol) === "LIKE") {
      $value = "%" . $value . "%";
    }

    $this->appendParameters(":$where", $value);

    return $this;
  }

  /**
   * Setting order condition
   * @method order
   * @param  string $order order parameter
   * @param  bool $desc  use desc or not
   * @return Database        return this database
   */

  public function order($order, $desc = false)
  {
    $this->_order = " ORDER BY $order";

    if($desc) {
      $this->_order .= " DESC";
    }

    return $this;
  }

  /**
   * Setting limit condition
   * @method limit
   * @param  integer  $limit  limit number
   * @param  integer $offset offset number
   * @return Database          return this database
   */

  public function limit($limit, $offset = 0)
  {
    $this->_limit = " LIMIT $offset, $limit";

    return $this;
  }

  /**
   * Prepare the statement handler
   * @method query
   * @param  string $queryString the query string
   * @return null
   */

  public function query($queryString)
  {
    $this->sth = $this->db->prepare($queryString);
  }

  /**
   * get last insert sql id
   * @method lastInsertId
   * @return integer       last insert id
   */

  public function lastInsertId()
  {
    return $this->db->lastInsertId();
  }

  /**
   * execute the statement
   * @method execute
   * @return null
   */

  private function execute()
  {
    $this->sth->execute($this->parameters);
  }

  public function rowCount()
  {
    return $this->sth->rowCount();
  }

  public function first($type = null)
  {
    switch ($type) {
      case 'array':
        $type = \PDO::FETCH_ASSOC;
        break;

      default:
        $type = \PDO::FETCH_OBJ;
        break;
    }

    $this->query($this->_createSelectQueryString());

    $this->execute();

    return $this->fetch($type);
  }

  public function get($type = null)
  {
    switch ($type) {
      case 'array':
        $type = \PDO::FETCH_ASSOC;
        break;

      default:
        $type = \PDO::FETCH_OBJ;
        break;
    }

    $this->query($this->_createSelectQueryString());

    $this->execute();

    return $this->rowCount() === 1 ? $this->fetch($type) : $this->fetchAll($type);
  }

  public function save()
  {
    if($this->_isNew()) {
      $this->query($this->_createInsertQueryString());
    } else {
      $this->query($this->_createUpdateQueryString());
      $this->appendParameters(":id", $this->get("id"));
    }

    $this->execute();

    if(! isset($this->id)) {
      $this->id = $this->lastInsertId();
    }
  }

  private function _createSelectQueryString()
  {
    $query = "SELECT";

    if(count($this->_select) === 0) {
      $query .= " * FROM $this->tableName";
    } else {
      foreach($this->_select as $key=> $value) {
        if($value === null) {
          $query .= " $key,";
        } else {
          $query .= " $key $value,";
        }
      }
      $query = rtrim($query, ",") . " FROM $this->tableName";
    }

    if($this->_where !== null) {
      $query .= $this->_where;
    }

    if($this->_order !== null) {
      $query .= $this->_order;
    }

    if($this->_limit !== null) {
      $query .= $this->_limit;
    }

    return $query;
  }

  private function _createInsertQueryString()
  {
    $query = "INSERT INTO `$this->tableName` (";

    $insertKey = implode(",", array_keys($this->properties));
    $insertValue = [];

    foreach($this->properties as $key=> $value) {
      $insertValue[":$key"] = $value;
    }

    $query .= $insertKey . ") VALUES (" . implode(", ", array_keys($insertValue)) . ")";

    $this->setParameters($insertValue);

    return $query;
  }

  private function _createUpdateQueryString()
  {
    $query = "UPDATE `$this->tableName` SET";
    $params = [];

    foreach($this->properties as $key=> $value) {
      if($key === "id") {
        continue;
      }

      $query .= " $key = :$key,";
      $params[":$key"] = $value;
    }

    $this->setParameters($params);

    return $query;
  }

  private function _isNew()
  {
    return ! array_key_exists("id", $this->properties);
  }

  public function fetch($type)
  {
    $this->properties = (array) $this->sth->fetch($type);
    return $this;
  }

  public function fetchAll($type)
  {
    $this->properties = $this->sth->fetchAll($type);
    return $this;
  }

  public function getProperty($name)
  {
    return $this->properties[$name];
  }

  public function set($name, $value)
  {
    $this->properties[$name] = $value;
  }

  public function has($name)
  {
    return array_key_exists($name, $this->properties);
  }

  public function __get($name)
  {
    return $this->getProperty($name);
  }

  public function __set($name, $value)
  {
    $this->set($name, $value);
  }

  public function count()
  {
    return count($this->properties);
  }

  public function getIterator()
  {
    return new \ArrayIterator($this->properties);
  }
}
