<?php
namespace Chestnut\Database\Connection;

use Chestnut\Contract\Database\ConnectionContract;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class MysqlConnection extends Connection implements ConnectionContract {
	protected $connection;

	public function connect($config) {
		$dsn = $this->getDsn($config);

		$connection = $this->createConnection($dsn, $config);

		$charset = isset($config['charset']) ? $config['charset'] : 'utf8';

		$connection->prepare("set names '$charset'")->execute();

		if (isset($config['timezone'])) {
			$connection->prepare(
				'set time_zone="' . $config['timezone'] . '"'
			)->execute();
		}

		if (isset($config['strict'])) {
			if ($config['strict']) {
				$connection->prepare("set session sql_mode='STRICT_ALL_TABLES'")->execute();
			} else {
				$connection->prepare("set session sql_mode=''")->execute();
			}
		}

		return $connection;
	}

	public function getDsn($config) {
		return isset($config['unix_socket']) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
	}

	public function getSocketDsn($config) {
		return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
	}

	public function getHostDsn($config) {
		return isset($config['port'])
		? "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']}"
		: "mysql:host={$config['host']};dbname={$config['db']}";
	}
}