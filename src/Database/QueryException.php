<?php
namespace Chestnut\Database;

use PDOException;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class QueryException extends PDOException {
	protected $query;
	protected $binds;

	function __construct($query, $previous) {
		parent::__construct('', 0, $previous);

		$this->query = $query;
		$this->code = $previous->getCode();

		$this->message = $this->convertMessage($query, $previous);

		if ($previous instanceof PDOException) {
			$this->errorInfo = $previous->errorInfo;
		}
	}

	public function convertMessage($query, $previous) {
		return $previous->getMessage() . "\n" . '(SQL: ' . $query . ')';
	}
}
