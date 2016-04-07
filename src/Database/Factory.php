<?php
namespace Chestnut\Database;

use Chestnut\Database\Connection\MysqlConnection;
use InvalidArgumentException;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Factory {
	protected $app;
	protected $driver;

	public function __construct($app) {
		$this->app = $app;
	}

	public function driver($driver = null) {
		return $this->setDriver($driver);
	}

	public function setDriver($driver) {
		if (is_null($driver)) {
			$driver = $this->getDefaultDriver();
		}

		$this->driver = $driver;

		return $this;
	}

	public function raw($string) {
		return new Raw($string);
	}

	public function newQuery() {
		$connection = $this->createConnection()->connect($this->getConfig());

		return new Connector($connection, $this->getConfig('prefix'));
	}

	public function getDefaultDriver() {
		return $this->app->config->get('database.default_driver');
	}

	public function getDriver() {
		return $this->driver ?: $this->getDefaultDriver();
	}

	public function getConfig($key = null) {
		if (!is_null($key)) {
			return $this->app->config->get('database.' . $this->driver . '.' . $key);
		}

		return $this->app->config->get('database.' . $this->driver);
	}

	public function createConnection() {
		switch ($this->driver) {
		case 'mysql':
			return new MysqlConnection();
		}

		throw new InvalidArgumentException('Unsupport Database Driver: ' . $this->driver);
	}

	public function __call($method, $params) {
		$query = $this->newQuery();

		return call_user_func_array([$query, $method], $params);
	}
}