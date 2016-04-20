<?php
namespace Chestnut\Database\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
abstract class Schema {

	protected $columns = [];
	protected $unique;
	protected $engine;
	protected $charset;
	protected $incrementIndex;

	public function __construct($table) {
		$this->table = $table;
	}

	public function addColumn($column, $type, $length = 255, $nullable = false, $default = '', $primary = false, $auto_increment = false) {
		$columnObj = new Column($column, $type, $length, $nullable, $default, $primary, $auto_increment);

		$this->columns[] = $columnObj;

		return $columnObj;
	}

	/**
	 * Setting Increment Key
	 * @param  string $name Key Name
	 * @return null
	 */
	public function increment($column) {
		return $this->addColumn($column, 'int', '11', false, '', true, true);
	}

	/**
	 * Setting Unique Keys
	 * @param  string|array $name Keys name
	 * @return null
	 */
	public function unique($cloumns) {
		if (is_string($cloumns)) {
			$cloumns = func_get_args();
		}

		$this->unique = $cloumns;
	}

	/**
	 * Setting Table Engine
	 * @param  string $engine Engine type
	 * @return null
	 */
	public function engine($engine) {
		$this->engine = $engine;
	}

	/**
	 * Setting Table Charset
	 * @param  string $charset Char Type
	 * @return null
	 */
	public function charset($charset) {
		$this->charset = $charset;
	}

	/**
	 * Setting Increment Start Index
	 * @param  integer $index Index
	 * @return null
	 */
	public function incrementIndex($index) {
		$this->increment = $index;
	}

	/**
	 * Add Timestamp Columns
	 * @param  string $create Create Column Name
	 * @param  string $update Update Column Name
	 * @return null
	 */
	public function timeStamp($create = 'created_at', $update = 'updated_at') {
		$this->addColumn($create, 'timestamp')->nullable(false)->defaults('CURRENT_TIMESTAMP');

		$this->addColumn($update, 'timestamp')->nullable(false)->defaults('0');
	}

	/**
	 * Add Soft Delete Column
	 * @param  string $softDelete Soft Delete Column Name
	 * @return null
	 */
	public function softDelete($softDelete = 'deleted_at') {
		$this->addColumn($softDelete, 'timestamp')->nullable(false)->default('0');
	}

	/**
	 * Build Create Table SQL
	 * @return string Create Table SQL
	 */
	abstract public function toSQL();

	public function __toString() {
		return $this->toSQL();
	}

	public function __call($key, $params) {
		if (method_exists($this, $params[0])) {
			$method = array_shift($params);
			array_unshift($params, $key);

			return call_user_func_array([$this, $method], $params);
		}

		array_unshift($params, $key);
		return call_user_func_array([$this, 'addColumn'], $params);
	}
}
