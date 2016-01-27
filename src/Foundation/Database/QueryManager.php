<?php namespace Chestnut\Foundation\Database;

use Chestnut\Support\Parameter;

class QueryManager {
	protected $table;
	protected $connection;
	protected $parameters;
	protected $model;

	public function __construct($table) {
		$this->connection = new Connection;
		$this->table = $this->convertTableName($table);
		$this->sql = new SQLManager($this->table);

		$this->parameters = new Collection;
	}

	public function convertTableName($table) {
		$table = explode("\\", $table);

		return $this->connection->getPrefix() . toUnderline(array_pop($table));
	}

	public function bindModel(Model $model) {
		$this->model = &$model;
	}

	public function select($columns = ['*']) {
		if (is_string($columns) && func_num_args() == 1) {
			$columns = explode(',', $columns);
		}

		if (is_string($columns) && func_num_args() > 1) {
			$columns = func_get_args();
		}

		$this->sql->set('select', $columns);

		return $this;
	}

	public function where($column, $symbol, $value = null, $link = 'AND') {
		if ($value === null && !in_array(strtoupper($symbol), ["＝", "<>", "<", ">", ">=", "<=", "LIKE", "IN"])) {
			$value = $symbol;
			$symbol = "=";
		}

		if ((int) $this->sql->length("where") === 0) {
			$this->sql->set("where", [$column => ['symbol' => $symbol]]);
		} else {
			$this->sql->set("where", [$column => ['symbol' => $symbol, 'link' => $link]]);
		}

		$this->parameters->set(":$column", $value);

		return $this;
	}

	public function whereOr($column, $symbol, $value = null) {
		return $this->where($column, $symbol, $value, 'OR');
	}

	public function whereBetween($column, $values, $link = 'AND') {
		if ((int) $this->sql->length("where") === 0) {
			$this->sql->set("where", [$column => ['symbol' => 'BETWEEN']]);
		} else {
			$this->sql->set("where", [$column => ['symbol' => 'BETWEEN', 'link' => $link]]);
		}

		foreach ($values as $index => $value) {
			$this->parameters->set("$column$index", $value);
		}

		return $this;
	}

	public function whereOrBetween($column, $values) {
		return $this->whereBetween($column, $values, 'OR');
	}

	public function freshWhere() {
		$this->sql->remove('where');
		$this->parameters->replace([]);
	}

	public function freshSelect() {
		$this->sql->remove('select');
	}

	public function orderBy($column, $sort = 'DESC') {
		$this->sql->set("order", [$column => $sort]);
		return $this;
	}

	public function groupBy($columns) {
		if (is_string($columns) && func_num_args() == 1) {
			$columns = explode(',', $columns);
		}

		if (is_string($columns) && func_num_args() > 1) {
			$columns = func_get_args();
		}

		$this->sql->set('group', $columns);

		return $this;
	}

	public function limit($limit, $offset = 0) {
		$this->sql->set('limit', compact('limit', 'offset'));

		return $this;
	}

	public function take($number) {
		$this->limit($number);

		return $this;
	}

	public function get($columns = ['*']) {
		if (!$this->sql->has('select')) {
			$this->select($columns);
		}

		try {
			$this->connection
				->query($this->getQueryString())
				->execute($this->parameters);

			if ($fetch = $this->connection->fetchAll(\PDO::FETCH_OBJ)) {
				return $this->applyToModel($fetch);
			} else {
				return false;
			}
		} catch (\PDOException $e) {
			if ($e->getCode() === 42 && method_exists($this->model, 'schema')) {
				$schema = new Schema($this->table);
				call_user_func([$this->model, 'schema'], $schema);
				$this->connection->query($schema->create())->execute();

				return $this->get($columns);
			}

			throw $e;
		}
	}

	public function all($columns = ['*']) {
		return $this->get($columns);
	}

	public function one($where = null, $columns = ['*']) {
		if (!is_null($where)) {
			if (!is_array($where)) {
				$this->where('id', $where);
			} else {
				foreach ($where as $column => $value) {
					$this->where($column, $value);
				}
			}
		}

		if (!$this->sql->has('select')) {
			$this->select($columns);
		}

		try {
			$this->connection
				->query($this->getQueryString())
				->execute($this->parameters);

			if ($fetch = $this->connection->fetch(\PDO::FETCH_OBJ)) {
				return $this->applyToModel($fetch);
			} else {
				return false;
			}
		} catch (\PDOException $e) {
			if ($e->getCode() === 42 && method_exists($this->model, 'schema')) {
				$schema = new Schema($this->table);
				call_user_func([$this->model, 'schema'], $schema);
				$this->connection->query($schema->create())->execute();

				return $this->one($where, $columns);
			}

			throw $e;
		}
	}

	public function count($columns = ['count(*) as count']) {
		$this->freshSelect();
		$this->select($columns);

		$this->connection
			->query($this->getQueryString())
			->execute($this->parameters);

		if ($fetch = $this->connection->fetch(\PDO::FETCH_OBJ)) {
			return $fetch->count;
		} else {
			return false;
		}
	}

	public function find($where = null, $columns = ['*']) {
		if (!is_null($where)) {
			if (!is_array($where)) {
				$this->where('id', $where);
			} else {
				foreach ($where as $column => $value) {
					$this->where($column, $value);
				}
			}
		}

		return $this->get($columns);
	}

	public function first($where, $columns = ['*']) {
		return $this->one($where, $columns);
	}

	public function applyToModel($fetch) {
		if (is_array($fetch)) {
			$collection = new Parameter();
			$class = get_class($this->model);

			foreach ($fetch as $item) {
				$instance = new $class((array) $item);

				foreach ($this->getRelations()->keys() as $relationName) {
					$instance->with($relationName);
				}

				$collection->push($instance);
			}

			return $collection;
		}

		$this->fill((array) $fetch);
		$this->processRelations();

		return $this;
	}

	public function insert() {
		$this->setTimestamp();

		try {
			$this->connection
				->query($this->getQueryString('insert', $this->getProperties()))
				->execute($this->getProperties());

			if ($id = $this->connection->lastInsertId()) {
				$this->getProperties()->set('id', $id);
				$this->applyToModel($this->getProperties(true)->toArray());
			}
		} catch (\PDOException $e) {
			if ($e->getCode() === 42 && method_exists($this->model, 'schema')) {
				$schema = new Schema($this->table);
				call_user_func([$this->model, 'schema'], $schema);
				$this->connection->query($schema->create())->execute();

				return $this->insert();
			}

			throw $e;
		}
	}

	public function update() {

		if (empty($dirty = $this->getProperties()->compare($this->getOrigin()))) {
			return;
		}

		$this->setTimestamp('update');

		$this->connection
			->query($this->getQueryString('update', $dirty))
			->execute($dirty->merge($this->parameters));
	}

	public function delete($where = null) {
		if (is_null($where)) {
			$where = $this->getProperties(true)->get('id');
		}

		$this->freshWhere();

		if (!is_array($where)) {
			$this->where('id', $where);
		} else {
			foreach ($where as $column => $value) {
				$this->where($column, $value);
			}
		}

		if ($this->isSoftDelete()) {
			$this->setSoftDelete();
			return $this->update();
		}

		$this->connection
			->query($this->getQueryString('delete'))
			->execute($this->parameters);
	}

	public function create($array) {
		foreach (array_keys($array) as $key) {
			if (in_array($key, $this->getGuarded())) {
				throw new \RuntimeException('The parameter {' . $key . '} has been guarded, please check');
			}

			if (!in_array($key, $this->getFill())) {
				throw new \RuntimeException('Can\'t create ' . $this->getModelName() . ' with {' . $key . '}');
			}

			if ($key === $this->getPrimaryKey()) {
				throw new \RuntimeException('Can\'t create ' . $this->getModelName() . ' with the primary key {id}');
			}
		}

		$this->setProperties($array);

		$this->insert();

		return $this;
	}

	public function alias($alias) {
		$this->sql->set('alias', $alias);

		return $this;
	}

	public function getQueryString($mode = 'select', $parameters = []) {
		return $this->sql->{'create' . ucfirst($mode)}($parameters);
	}

	public function getSql() {
		return $this->sql;
	}

	public function getParameters() {
		return $this->parameters;
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->model, $method], $params);
	}
}