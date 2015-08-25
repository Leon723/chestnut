<?php namespace Chestnut\Nut;

class Database
{
  /**
   * 数据链接
   * @var PDO
   */
  protected $db;

  /**
   * 查询生命
   * @var PDOStatement
   */
  protected $sth;

  /**
   * 构造函数
   */
  public function __construct()
  {
    $dbc = \Chestnut\Core\Registry::get('config')->get('database');
    $debug = \Chestnut\Core\Registry::get('config')->get('debug');

    try {
      $this->db = new \PDO("mysql:host=" . $dbc['mysql']['host'] . ";dbname=" . $dbc['mysql']['dbname'], $dbc['mysql']['user'], $dbc['mysql']['password'],[\PDO::MYSQL_ATTR_INIT_COMMAND=> "SET NAMES 'utf8';"]);
      $this->db->setAttribute(\PDO::ATTR_ERRMODE, $debug ? \PDO::ERRMODE_EXCEPTION : \PDO::ERRMODE_SILENT);
    }
    catch(\PDOException $e) {
      echo $e->getMessage();
    }
  }

  public function query($sql)
  {
    $this->sth = $this->db->prepare($sql);
    return $this;
  }

  public function execute($parameters = [])
  {
    $this->sth->execute($parameters);
    return $this;
  }

  public function fetch($type)
  {
    return $this->sth->fetch($type);
  }

  public function fetchAll($type)
  {
    return $this->sth->fetchAll($type);
  }

  public function lastInsertId()
  {
    return $this->sth->lastInsertId();
  }

  public function count()
  {
    return $this->sth->rowCount();
  }
}
