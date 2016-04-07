<?php
namespace Chestnut\Database;

use PDO;
use PDOException;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Connector {
	/**
	 * PDO Connection
	 * @var PDO
	 */
	protected $connection;

	/**
	 * Table Prefix
	 * @var string
	 */
	protected $tablePrefix;

	/**
	 * Fetch Type
	 * @var mixed
	 */
	protected $fetchType = PDO::FETCH_OBJ;

	/**
	 * Query Logs
	 * @var array
	 */
	protected $queryLog = [];

	/**
	 * \Chestnut\Database\Connector Construct
	 * @param PDO $connection
	 * @param [type] $table      [description]
	 */
	public function __construct($connection, $tablePrefix, $fetchType = null) {
		$this->connection = $connection;
		$this->tablePrefix = $tablePrefix;

		if (!is_null($fetchType)) {
			$this->fetchType = $fetchType;
		}
	}

	public function getPrefix() {
		return $this->tablePrefix;
	}

	public function getLastInsertId() {
		return $this->connection->lastInsertId;
	}

	public function select($sql, $binds = []) {
		return $this->run($sql, $binds, function ($sth, $binds) {
			$sth->execute($binds);

			return $sth->fetchAll($this->fetchType);
		});
	}

	public function update($sql, $binds = []) {
		return $this->affectingStatement($sql, $binds);
	}

	public function insert($sql, $binds = []) {
		return $this->run($sql, $binds, function ($sth, $binds) {
			$result = $sth->execute($binds);

			return $result;
		});
	}

	public function delete($sql, $binds = []) {
		return $this->affectingStatement($sql, $binds);
	}

	public function affectingStatement($query, $binds) {
		return $this->run($query, $binds, function ($sth, $binds) {
			$sth->execute($binds);

			return $sth->rowCount();
		});
	}

	public function run($query, $binds, $callback) {
		try {
			$startTime = microtime();

			$result = $this->runQuery($query, $binds, $callback);

			$endTime = microtime();

			$this->appendQueryLog($query, $binds, $endTime - $startTime);
		} catch (QueryException $e) {
			throw $e;
		}

		return $result;
	}

	public function convertQueryString($query, $binds) {
		return preg_replace_callback('/\?/', function ($m) use (&$binds) {
			return array_shift($binds);
		}, $query);
	}

	public function appendQueryLog($query, $binds, $execTime) {
		$query = $this->convertQueryString($query, $binds);

		$this->queryLog[] = [
			$query,
			$execTime,
		];
	}

	public function getLastExecuteQuery() {
		return end($this->queryLog);
	}

	public function runQuery($query, $binds, $callback) {
		try {
			$sth = $this->getConnection()->prepare($query);
			$result = $callback($sth, $binds);
		} catch (PDOException $e) {
			$query = $this->convertQueryString($query, $binds);
			throw new QueryException($query, $e);
		}

		return $result;
	}

	public function getConnection() {
		return $this->connection;
	}
}
