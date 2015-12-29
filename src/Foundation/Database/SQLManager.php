<?php namespace Chestnut\Foundation\Database;

use Chestnut\Support\Parameter;

class SQLManager extends Parameter {

	public function __construct($table) {
		parent::__construct();

		$this->set('table', $table);
	}

	/**
	 * 设置属性
	 * @param string $key   属性名
	 * @param any $value 属性值
	 */
	public function set($key, $value) {
		if (is_array($value) && !$this->has($key)) {
			$this->attributes[$key] = [];
		}

		if (is_array($value)) {
			$this->attributes[$key] = array_merge($this->attributes[$key], $value);
			return;
		}

		parent::set($key, $value);
	}

	/**
	 * 创建查询语句
	 * @return string
	 */
	public function createSelect() {
		$query = "SELECT ";

		foreach ($this->get('select', ['*']) as $select) {
			if ($select === '*') {
				$query .= "$select,";
			} else {
				$query .= $this->getTableAlias() . ".`$select`,";
			}
		}

		return rtrim($query, ",")
		. " FROM "
		. $this->getTable()
		. $this->getWhere()
		. $this->getGroup()
		. $this->getOrder()
		. $this->getLimit()
			. ';';
	}

	/**
	 * 创建插入语句
	 * @param  array $parameters 参数名数组
	 * @return string  插入语句
	 */
	public function createInsert($parameters) {
		$query = "INSERT INTO "
		. $this->getTable()
		. " ({$this->getTableAlias()}.`" . $parameters->joinKeys("`, {$this->getTableAlias()}.`")
		. "`) "
		. "VALUES (:"
		. $parameters->joinKeys(', :')
			. ")";

		return $query . ';';
	}

	/**
	 * 创建更新语句
	 * @param  array $parameters 参数名数组
	 * @return string 更新语句
	 */
	public function createUpdate($parameters) {
		$query = "UPDATE "
		. $this->getTable()
			. " SET";

		foreach ($parameters->keys() as $key) {
			$query .= " {$this->getTableAlias()}.`$key` = :$key,";
		}

		return rtrim($query, ",") . $this->getWhere() . ';';
	}

	/**
	 * 创建删除语句
	 * @return string 删除语句
	 */
	public function createDelete() {
		$query = "DELETE FROM " . $this->getTable();

		return $query . $this->getWhere() . ';';
	}

	/**
	 * 获取表名，如果存在表别名则返回别名
	 * @return string 表名
	 */
	public function getTableAlias() {
		return "`{$this->get('alias', $this->get('table'))}`";
	}

	/**
	 * 获取表名，并为表名设置别名
	 * @return string 表名
	 */
	public function getTable() {
		return $this->has('alias') ? "`{$this->get('table')}` as `{$this->get('alias')}`" : "`{$this->get('table')}`";
	}

	/**
	 * 获取查询条件语句
	 * @return string 查询条件语句
	 */
	public function getWhere() {
		if (!$this->has('where')) {
			return '';
		}

		$whereSQL = ' WHERE';

		foreach ($this->get("where") as $column => $config) {
			$whereSQL .= $this->convertWhereString($column, $config);
		}

		return $whereSQL;
	}

	public function getOrder() {
		if (!$this->has('order')) {
			return '';
		}

		$orderSQL = ' ORDER BY ';

		foreach ($this->get("order") as $column => $sort) {
			$orderSQL .= $this->getTableAlias() . ".`$column` $sort";
		}

		return $orderSQL;
	}

	public function getGroup() {
		if (!$this->has('group')) {
			return '';
		}

		$groupSQL = ' GROUP BY ';

		foreach ($this->get("group") as $column) {
			$groupSQL .= $this->getTableAlias() . ".`$column`";
		}

		return $groupSQL;
	}

	public function getLimit() {
		return $this->has('limit') ? ' LIMIT ' . $this->get('limit.offset') . ', ' . $this->get('limit.limit') : '';
	}

	/**
	 * 转化查询语句
	 * @param  string $column 列名
	 * @param  array $config 查询参数
	 * @return string         查询语句
	 */
	public function convertWhereString($column, $config) {
		$result = ' ';

		if (array_key_exists('link', $config)) {
			$result .= $config['link'] . ' ';
		}

		if ($config['symbol'] === 'BETWEEN') {
			$result .= $this->getTableAlias() . ".`$column` {$config['symbol']} :{$column}0 AND :{$column}1";
		} else {
			$result .= $this->getTableAlias() . ".`$column` {$config['symbol']} :$column";
		}

		return $result;
	}

}
