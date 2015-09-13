<?php namespace Chestnut\Core\Nut;

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
   * 查询错误
   * @var array
   */
  protected $error;

  /**
   * 构造函数
   */
  public function __construct()
  {
    $debug = config('app.debug', true);

    try {
      $this->db = new \PDO(
                "mysql:host=" . config('database.mysql.host')
                . ";dbname=" . config('database.mysql.dbname'),
                config('database.mysql.user'),
                config('database.mysql.password'),
                [\PDO::MYSQL_ATTR_INIT_COMMAND=> "SET NAMES 'utf8';"]
      );

      $this->db->setAttribute(\PDO::ATTR_ERRMODE, $debug ? \PDO::ERRMODE_EXCEPTION : \PDO::ERRMODE_SILENT);
    }
    catch(\PDOException $e) {
      $this->error = (object) ["code"=>$e->getCode(), "message"=> $e->getMessage()];
    }
  }

  public function query($sql)
  {
    $this->sth = $this->db->prepare($sql);
    return $this;
  }

  public function execute($parameters = [])
  {
    try{
      $this->sth->execute($parameters);
    } catch(\PDOException $e) {
      $this->error = (object) ["code"=>$e->getCode(), "message"=> $e->getMessage()];
    }

    return $this;
  }

  public function getError()
  {
    return $this->error;
  }

  public function clearError()
  {
    $this->error = null;
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
    return $this->db->lastInsertId();
  }

  public function count()
  {
    return $this->sth->rowCount();
  }
}
