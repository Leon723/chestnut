<?php
namespace Chestnut\Database\Connection;

use Exception;
use PDO;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
abstract class Connection {
	protected $options = [
		PDO::ATTR_CASE => PDO::CASE_NATURAL,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES => false,
		PDO::ATTR_EMULATE_PREPARES => false,
	];

	protected $connection;

	public function createConnection($dsn, $config) {
		$user = $config['user'];
		$password = $config['password'];

		$options = isset($config['options']) ? array_merge($this->options, $config['options']) : $this->options;

		try {
			return new Pdo($dsn, $user, $password, $options);
		} catch (Exception $e) {
			throw $e;
		}
	}

	abstract function getDsn($config);
}