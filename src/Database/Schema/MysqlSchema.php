<?php
namespace Chestnut\Database\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class MysqlSchema extends Schema {
	public function __construct($table) {
		parent::__construct($table);

		$this->engine('INNODB');
		$this->charset("UTF8");
	}

	public function string($column, $length = 255, $nullable = false, $default = '') {
		return $this->addColumn($column, 'varchar', $length, $nullable, $default);
	}

	public function integer($column, $length = 11, $nullable = false, $default = '') {
		return $this->addColumn($column, 'int', $length, $nullable, $default);
	}

	public function tinyinteger($column, $length = 4, $nullable = false, $default = '') {
		return $this->addColumn($column, 'tinyint', $length, $nullable, $default);
	}

	public function double($column, $length = [11, 2], $nullable = false, $default = '') {
		return $this->addColumn($column, 'float', $length, $nullable, $default);
	}

	public function toSQL() {
		$result = "CREATE TABLE " . $this->table
			. " (";

		foreach ($this->columns as $column) {
			$result .= trim($column) . ',';
		}

		if ($this->unique) {
			foreach ($this->unique as $key) {
				$result .= "UNIQUE KEY(`$key`),";
			}
		}

		$result = rtrim($result, ",") . ")ENGINE=$this->engine DEFAULT CHARSET=$this->charset";

		if ($this->incrementIndex) {
			$result .= " AUTO_INCREMENT=" . $this->incrementIndex;
		}

		return $result .= ';';
	}
}
