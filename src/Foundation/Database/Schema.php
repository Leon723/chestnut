<?php
namespace Chestnut\Foundation\Database;

use Chestnut\Support\Parameter;

class Schema extends Parameter {

	public function __construct($table) {
		$this->table = $table;
		$this->engine('INNODB');
		$this->charset("UTF8");
	}

	public function increment($name) {
		$this->set($name, [
			"type" => "INT",
			"nullable" => "NOT NULL",
			"increment" => "AUTO_INCREMENT",
		]);

		$this->primary("id");
	}

	public function primary($name) {
		if ($this->has('primary')) {
			throw new \Exception('The primary key has been set to {' . $this->get('primary') . '}');
		}
		$this->set('primary', $name);
	}

	public function unique($name) {
		if (is_string($name)) {
			$name = func_get_args();
		}

		$this->set('unique', $name);
	}

	public function engine($engine) {
		$this->set('engine', $engine);
	}

	public function charset($charset) {
		$this->set('charset', $charset);
	}

	public function incrementIndex($index) {
		$this->set('increment', $index);
	}

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

	public function text($name, $nullable = true) {
		$this->set($name, [
			"type" => "TEXT",
			"nullable" => $nullable ? "NULL" : "NOT NULL",
		]);
	}

	public function timeStamp() {
		$this->set("created_at", [
			"type" => "TIMESTAMP",
		]);

		$this->set("updated_at", [
			"type" => "TIMESTAMP",
		]);
	}

	public function softDelete() {
		$this->set("deleted_at", [
			"type" => "TIMESTAMP",
		]);
	}

	public function propertyToString($key) {
		return join($this->{$key}, " ");
	}

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
