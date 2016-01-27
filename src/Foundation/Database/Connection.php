<?php
namespace Chestnut\Foundation\Database;

use PDOException;

class Connection {
	protected $db;

	protected $sth;

	protected $prefix;

	public function __construct($type = 'mysql') {
		$config = config('database.' . $type);
		$this->createConnection($type, $config);
	}

	public function query($sql) {
		$this->sth = $this->db->prepare($sql);
		return $this;
	}

	public function execute($parameters = []) {
		try {
			$this->sth->execute($parameters instanceof Collection ? $parameters->toArray() : $parameters);
			return $this;
		} catch (PDOException $e) {
			throw $e;
		}
	}

	public function createConnection($type, $config) {
		switch ($type) {
		case 'mysql':
			$debug = config('app.debug', false);
			$connect = "mysql:host=" . $config['host'] . ";dbname=" . $config['dbname'];
			try {
				$this->db = new \PDO($connect, $config['user'], $config['password'],
					[\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"]
				);

				$this->db->setAttribute(\PDO::ATTR_ERRMODE, $debug ? \PDO::ERRMODE_EXCEPTION : \PDO::ERRMODE_SILENT);

				$this->prefix = $config['prefix'];
			} catch (\PDOException $e) {
				throw $e;
			}
			break;
		}
	}

	public function getPrefix() {
		return $this->prefix;
	}

	public function fetch($type) {
		return $this->sth->fetch($type);
	}

	public function fetchAll($type) {
		return $this->sth->fetchAll($type);
	}

	public function lastInsertId() {
		return $this->db->lastInsertId();
	}

	public function count() {
		return $this->sth->rowCount();
	}
}