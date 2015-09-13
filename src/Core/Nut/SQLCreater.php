<?php namespace Chestnut\Core\Nut;

use Chestnut\Core\Config\Config;

class SQLCreater extends Config
{

  /**
   * 设置属性
   * @param string $key   属性名
   * @param any $value 属性值
   */
  public function set($key, $value)
  {
    if(is_array($value) && ! $this->has($key)) {
      $this->attributes[$key] = [];
    }

    if(is_array($value)) {
      $this->attributes[$key] = array_merge($this->attributes[$key], $value);
      return;
    }

    parent::set($key, $value);
  }

  /**
   * 返回属性的长度
   * @param  string $key 属性名
   * @return integer      长度
   */
  public function sizeof($key)
  {
    return $this->has($key) ? count($this->get($key)) : 0;
  }

  /**
   * 创建查询语句
   * @return string
   */
  public function createSelect()
  {
    $query = "SELECT ";

    foreach($this->get('select') as $select=> $alias) {
      if($alias === null) {
        $query .= "$select,";
        continue;
      }

      $query .= "$select $alias,";
    }

    $condition = "";

    if($this->sizeof("where")) {
      foreach($this->get("where") as $where=> $value) {
        $condition .= " $where $value";
      }
    }

    if($this->has('order')) {
      $condition .= " " . $this->get('order');
    }

    if($this->has('limit')) {
      $condition .= " " . $this->get('limit');
    }

    return rtrim($query, ",") . " FROM " . $this->get('table') . $condition;
  }

  /**
   * 创建插入语句
   * @param  array $parameters 参数名数组
   * @return string  插入语句
   */
  public function createInsert($parameters)
  {
    $query = "INSERT INTO "
           . $this->get("table")
           . " (" . implode(", ", $parameters)
           . ") "
           . "VALUES (:"
           . implode(", :", $parameters)
           . ")";

    return $query;
  }

  /**
   * 创建更新语句
   * @param  array $parameters 参数名数组
   * @return string 更新语句
   */
  public function createUpdate($parameters)
  {
    $query = "UPDATE "
           . $this->get('table')
           . " SET";

    $condition = "";

    foreach($parameters as $key) {
      $query .= " $key = :$key,";
    }

    if($this->sizeof("where")) {
      foreach($this->get("where") as $where=> $value) {
        $condition .= " $where $value";
      }
    }

    return rtrim($query, ",") . $condition;
  }

  /**
   * 创建删除语句
   * @return string 删除语句
   */
  public function createDelete()
  {
    $query = "DELETE FROM " . $this->get('table');
    $condition = "";

    if($this->sizeof("where")) {
      foreach($this->get("where") as $where=> $value) {
        $condition .= " $where $value";
      }
    }

    return "$query $condition";
  }

}
