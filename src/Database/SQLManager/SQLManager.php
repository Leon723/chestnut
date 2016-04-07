<?php
namespace Chestnut\Database\SQLManager;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
abstract class SQLManager {
	protected $table;
	protected $prefix;

	public function __construct($table, $prefix) {
		$this->table = $table;
		$this->prefix = $prefix;
	}

	abstract function parseInsert($query, $params);
	abstract function parseDelete($query);
	abstract function parseUpdate($query, $params);
	abstract function parseSelect($query);

	public function wrap($column) {
		$column = explode(' as ', $column);

		return count($column) > 1 ? "`{$this->prefix}{$this->table}`.`{$column[0]}` as `{$column[1]}`" : "`{$this->prefix}{$this->table}`.`{$column[0]}`";
	}

	public function wrapTable() {
		$table = explode(' as ', $this->table);

		return count($table) > 1 ? "`{$this->prefix}{$this->table}` as `{$table[1]}`" : "`{$this->prefix}{$this->table}`";
	}
}