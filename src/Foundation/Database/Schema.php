<?php
namespace Chestnut\Foundation\Database;

use Chestnut\Support\Parameter;

class Schema extends Parameter {

	public function __construct($table) {
		$this->table = $table;
		$this->engine('INNODB');
		$this->charset("UTF8");
	}

	/**
	 * Setting Increment Key
	 * @param  string $name Key Name
	 * @return null
	 */
	public function increment($name) {
		$this->set($name, [
			"type" => "INT",
			"nullable" => "NOT NULL",
			"increment" => "AUTO_INCREMENT",
		]);

		$this->primary("id");
	}

	/**
	 * Setting Primary Key
	 * @param  string $name Key Name
	 * @return null
	 */
	public function primary($name) {
		if ($this->has('primary')) {
			throw new \Exception('The primary key has been set to {' . $this->get('primary') . '}');
		}
		$this->set('primary', $name);
	}

	/**
	 * Setting Unique Keys
	 * @param  string|array $name Keys name
	 * @return null
	 */
	public function unique($name) {
		if (is_string($name)) {
			$name = func_get_args();
		}

		$this->set('unique', $name);
	}

	/**
	 * Setting Table Engine
	 * @param  string $engine Engine type
	 * @return null
	 */
	public function engine($engine) {
		$this->set('engine', $engine);
	}

	/**
	 * Setting Table Charset
	 * @param  string $charset Char Type
	 * @return null
	 */
	public function charset($charset) {
		$this->set('charset', $charset);
	}

	/**
	 * Setting Increment Start Index
	 * @param  integer $index Index
	 * @return null
	 */
	public function incrementIndex($index) {
		$this->set('increment', $index);
	}

	/**
	 * Add Varchar Column
	 * @param  string  $name     Column Name
	 * @param  integer $length   Column Length
	 * @param  boolean $nullable Nullable
	 * @return null
	 */
	public function string($name, $length = 255, $nullable = false) {
		if (is_bool($length)) {
			$nullable = $length;
			$length = 255;
		}

		$this->set($name, [
			"type" => "VARCHAR($length)",
			"nullable" => $nullable ? "NULL" : "NOT NULL",
		]);
	}

	/**
	 * Add Integer Column
	 * @param  string  $name     Column name
	 * @param  integer $length   Column Length
	 * @param  boolean $nullable Nullable
	 * @return null
	 */
	public function integer($name, $length = 11, $nullable = false) {
		if (is_bool($length)) {
			$nullable = $length;
			$length = 11;
		}

		$this->set($name, [
			"type" => "INT($length)",
			"nullable" => $nullable ? "NULL" : "NOT NULL",
		]);
	}

	/**
	 * Add Tinyinteger Column
	 * @param  string  $name     Column Name
	 * @param  integer $length   Column Length
	 * @param  boolean $nullable Nullable
	 * @return null
	 */
	public function tinyinteger($name, $length = 4, $nullable = false) {
		if (is_bool($length)) {
			$nullable = $length;
			$length = 4;
		}

		$this->set($name, [
			"type" => "TINYINT($length)",
			"nullable" => $nullable ? "NULL" : "NOT NULL",
		]);
	}

	/**
	 * Add Text Column
	 * @param  string  $name     Column Name
	 * @param  boolean $nullable Nullable
	 * @return null
	 */
	public function text($name, $nullable = true) {
		$this->set($name, [
			"type" => "TEXT",
			"nullable" => $nullable ? "NULL" : "NOT NULL",
		]);
	}

	/**
	 * Add Timestamp Columns
	 * @param  string $create Create Column Name
	 * @param  string $update Update Column Name
	 * @return null
	 */
	public function timeStamp($create = 'created_at', $update = 'updated_at') {
		$this->set($create, [
			"type" => "TIMESTAMP",
		]);

		$this->set($update, [
			"type" => "TIMESTAMP",
		]);
	}

	/**
	 * Add Soft Delete Column
	 * @param  string $softDelete Soft Delete Column Name
	 * @return null
	 */
	public function softDelete($softDelete = 'deleted_at') {
		$this->set($softDelete, [
			"type" => "TIMESTAMP",
		]);
	}

	/**
	 * Build Create Table SQL
	 * @return string Create Table SQL
	 */
	public function create() {
		$result = "CREATE TABLE " . $this->table
			. "(";

		$parameter = "";

		foreach ($this->filter(['primary', 'unique', 'engine', 'charset', 'table', 'increment'])->get('table') as $key => $value) {
			$parameter .= "`$key` " . $this->join(' ', $key) . ",";
		}

		$result .= $parameter . "PRIMARY KEY(" . $this->get("primary") . "),";

		if ($this->has('unique')) {
			foreach ($this->get('unique') as $key) {
				$result .= "UNIQUE KEY(`$key`),";
			}
		}

		$result = rtrim($result, ",") . ")ENGINE=$this->engine DEFAULT CHARSET=$this->charset";

		if ($this->has('increment')) {
			$result .= " AUTO_INCREMENT=" . $this->get('increment');
		}

		return $result .= ';';
	}

	public function __get($key) {
		return $this->get($key);
	}

	public function __set($key, $value) {
		$this->set($key, $value);
	}
}
