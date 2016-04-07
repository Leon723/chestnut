<?php
namespace Chestnut\Database\Query;

use Chestnut\Database\Connector;
use Chestnut\Database\SQLManager\MysqlSQLManager;
use PDO;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Query {
	/**
	 * Database Connector
	 * @var Chestnut\Database\Connector
	 */
	protected $connector;

	/**
	 * Database Driver
	 * @var string
	 */
	protected $driver;

	/**
	 * Fetch Type
	 * @var mixed
	 */
	protected $fetchType = PDO::FETCH_OBJ;

	/**
	 * Query Table
	 * @var string
	 */
	public $table;

	/**
	 * Select columns
	 * @var array
	 */
	public $columns;

	/**
	 * Query Where
	 * @var array
	 */
	public $where;

	/**
	 * Query GroupBy
	 * @var array
	 */
	public $group;

	/**
	 * Query OrderBy
	 * @var array
	 */
	public $order;

	/**
	 * Query Limit Number
	 * @var integer
	 */
	public $limit;

	/**
	 * Query Limit Offset
	 * @var integer
	 */
	public $offset;

	/**
	 * Determind Distinct Mode
	 * @var boolean
	 */
	public $distinct = false;

	/**
	 * Query String Binds
	 * @var array
	 */
	public $binds = [
		'insert' => [],
		'update' => [],
		'where' => [],
	];

	/**
	 * Query Where Operators
	 * @var array
	 */
	protected $operators = [
		'=', '<', '>', '<=', '>=', '<>', '!=',
		'like', 'like binary', 'not like', 'between', 'ilike',
		'&', '|', '^', '<<', '>>',
		'rlike', 'regexp', 'not regexp',
		'~', '~*', '!~', '!~*', 'similar to',
		'not similar to',
	];

	/**
	 * \Chestnut\Database\Query\Query Construct
	 * @param \Chestnut\Database\Connector $connector
	 * @param [type] $table      [description]
	 */
	public function __construct(Connector $connector, $driver) {
		$this->connector = $connector;
		$this->driver = $driver;
	}

	/**
	 * From table
	 * @param  string $table
	 * @return Chestnut\Database\Query\Query
	 */
	public function from($table) {
		return $this->table($table);
	}

	/**
	 * Query Table
	 * @param  string $table
	 * @return Chestnut\Database\Query\Query
	 */
	public function table($table) {
		$this->table = $table;

		return $this;
	}

	/**
	 * Query Select Columns
	 * @param  array  $columns
	 * @return Chestnut\Database\Query\Query
	 */
	public function select($columns = ['*']) {
		$this->columns = is_array($columns) ? $columns : func_get_args();

		return $this;
	}

	/**
	 * Query Where
	 * @param  string $column
	 * @param  string $symbol
	 * @param  mixed $value
	 * @param  string $link
	 * @return Chestnut\Database\Query\Query
	 */
	public function where($column, $symbol = '=', $value = null, $link = 'and') {
		if ($value === null && !in_array(strtoupper($symbol), $this->operators)) {
			list($value, $symbol) = [$symbol, '='];
		}

		$this->addWhere($column, $symbol, $link);

		$this->addBind('where', $value);

		return $this;
	}

	/**
	 * Query Or Where
	 * @param  string $column
	 * @param  string $symbol
	 * @param  mixed $value
	 * @return Chestnut\Database\Query\Query
	 */
	public function orWhere($column, $symbol, $value = null) {
		return $this->where($column, $symbol, $value, 'or');
	}

	/**
	 * Query Where In
	 * @param  string $column
	 * @param  mixed $value
	 * @param  string $link
	 * @return Chestnut\Database\Query\Query
	 */
	public function whereIn($column, $value, $link = 'and') {
		if (is_array($value)) {
			$value = implode($value, ',');
		}

		return $this->where($column, 'in', $value, $link);
	}

	/**
	 * Query orWhere In
	 * @param  string $column
	 * @param  mixed $value
	 * @return Chestnut\Database\Query\Query
	 */
	public function orWhereIn($column, $value) {
		return $this->whereIn($column, $value, 'or');
	}

	/**
	 * Query Where Like
	 * @param  string $column
	 * @param  mixed $value
	 * @param  string $link
	 * @return Chestnut\Database\Query\Query
	 */
	public function whereLike($column, $value = null, $link = 'and') {
		return $this->where($column, "like", $value, $link);
	}

	/**
	 * Query orWhere Like
	 * @param  string $column
	 * @param  mixed $value
	 * @return Chestnut\Database\Query\Query
	 */
	public function orWhereLike($column, $value) {
		return $this->whereLike($column, $value, 'or');
	}

	/**
	 * Query Where Between
	 * @param  string $column
	 * @param  mixed $values
	 * @param  string $link
	 * @return Chestnut\Database\Query\Query
	 */
	public function whereBetween($column, $values, $link = 'and') {
		return $this->where($column, 'between', $values, $link);
	}

	/**
	 * Query orWhere Between
	 * @param  string $column
	 * @param  mixed $values
	 * @return Chestnut\Database\Query\Query
	 */
	public function orWhereBetween($column, $values) {
		return $this->whereBetween($column, $values, 'or');
	}

	/**
	 * Query OrderBy
	 * @param  string $column
	 * @param  string $order
	 * @return Chestnut\Database\Query\Query
	 */
	public function orderBy($column, $order = 'desc') {
		if (is_array($column)) {
			foreach ($column as $col => $o) {
				if (is_integer($col)) {
					list($col, $o) = [$o, $order];
				}

				$this->order($col, $o);
			}
			return;
		}

		if (!isset($this->order)) {
			$this->order = [];
		}

		$this->order[$column] = $order;

		return $this;
	}

	/**
	 * Query GroupBy
	 * @param  string $column
	 * @return Chestnut\Database\Query\Query
	 */
	public function groupBy($column) {
		if (is_array($column)) {
			foreach ($column as $col) {

				$this->group($col);
			}
			return;
		}

		if (!isset($this->group)) {
			$this->group = [];
		}

		$this->group[] = compact('column');

		return $this;
	}

	/**
	 * Query Limit
	 * @param  integer  $limit
	 * @param  integer $offset
	 * @return Chestnut\Database\Query\Query
	 */
	public function limit($limit, $offset = 0) {
		return $this->take($limit)->skip($offset);
	}

	/**
	 * Query Limit Number
	 * @param  integer $take
	 * @return Chestnut\Database\Query\Query
	 */
	public function take($take) {
		$this->limit = $take;

		return $this;
	}

	/**
	 * Query Limit Offset
	 * @param  integer $skip
	 * @return Chestnut\Database\Query\Query
	 */
	public function skip($skip) {
		$this->offset = $skip;

		return $this;
	}

	/**
	 * Determind Distince Mode
	 * @return boolean
	 */
	public function isDistinct() {
		return $this->distinct;
	}

	/**
	 * Execute Select Query
	 * @param  array  $column
	 * @return mixed
	 */
	public function get($columns = ['*']) {
		if (!isset($this->columns)) {
			$this->select($columns);
		}

		$sql = $this->getSQLManager();

		$query = $sql->parseSelect($this);

		return $this->connector->select($query, $this->getBindings());
	}

	/**
	 * Execute Insert Query
	 * @return boolean isInsert
	 */
	public function insert($params) {
		$sql = $this->getSQLManager();

		$query = $sql->parseInsert($this, $params);

		return $this->connector->insert($query, $this->getBindings());
	}

	/**
	 * Execute Delete Query
	 * @return integer affect row count
	 */
	public function delete() {
		$sql = $this->getSQLManager();

		$query = $sql->parseDelete($this);

		return $this->connector->delete($query, $this->getBindings());
	}

	/**
	 * Execute Update Query
	 * @return integer affect row count
	 */
	public function update($params) {
		$sql = $this->getSQLManager();

		$query = $sql->parseUpdate($this, $params);

		return $this->connector->update($query, $this->getBindings());
	}

	/**
	 * Get Last Insert Id
	 * @return integer Last Insert Id
	 */
	public function getLastInsertId($name = 'id') {
		return $this->connector->getLastInsertId($name);
	}

	/**
	 * Get SQLManager
	 * @return Chestnut\Database\SQLManager
	 */
	public function getSQLManager() {
		switch ($this->driver) {
		case 'mysql':
			return new MysqlSQLManager($this->table, $this->connector->getPrefix());
		}
	}

	/**
	 * Get Last Execute QueryString
	 * @return array [QueryString, executeTime]
	 */
	public function lastQueryString() {
		return $this->connector->getLastExecuteQuery();
	}

	/**
	 * Add Where Condition to where array
	 * @param string $column
	 * @param string $symbol
	 * @param string $link
	 */
	public function addWhere($column, $symbol, $link = null) {
		if (!isset($this->where)) {
			$this->where = [];
		}

		$this->where[$column] = compact('symbol', 'link');
	}

	public function getWhere() {
		return $this->where;
	}

	/**
	 * Add QueryString Binds Parameter
	 * @param string $binds
	 * @param mixed $value
	 */
	public function addBind($binds, $value) {
		if (is_array($value)) {
			foreach ($value as $val) {
				$this->addBind($binds, $val);
			}
			return;
		}

		$this->binds[$binds][] = $value;
	}

	public function getBinds($bind = 'where') {
		return $this->binds[$bind];
	}

	public function getBindings() {
		$result = [];

		array_walk_recursive($this->binds, function ($item) use (&$result) {
			$result[] = $item;
		});

		return $result;
	}
}
