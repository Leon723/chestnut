<?php
namespace Chestnut\Database;

use PDOException;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Connection {
	protected $driver;

	protected $config;

	protected $db;

	protected $sth;

	public function __construct($driver = 'mysql') {
		$this->config = config('database.' . $driver);
		$this->driver = $driver;
	}

	public function query($sql) {
		$this->createConnection($this->driver, $this->config);

		$this->sth = $this->db->prepare($sql);

		return $this;
	}

	public function execute($parameters = []) {
		try {
			if ($parameters instanceof Collection) {
				$parameters = $parameters->toArray();
			}

			$this->sth->execute($parameters);
		} catch (PDOException $e) {
			throw $e;
		} finally {
			$this->db = null;
		}

		return $this;
	}

	public function createConnection($driver, $config) {
		switch ($driver) {
		case 'mysql':
			$debug = config('app.debug', false);
			$connect = "mysql:host=" . $config['host'] . ";dbname=" . $config['dbname'];
			try {
				$this->db = new \PDO($connect, $config['user'], $config['password'],
					[\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"]
				);

				$this->db->setAttribute(\PDO::ATTR_ERRMODE, $debug ? \PDO::ERRMODE_EXCEPTION : \PDO::ERRMODE_SILENT);
			} catch (\PDOException $e) {
				throw $e;
			}
			break;
		}
	}

	public function getPrefix() {
		return $this->config['prefix'];
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