<?php
namespace Chestnut\Database;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
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

	public function whereIn($column, $value, $link = 'AND') {
		if (is_array($value)) {
			$value = implode($value, ',');
		}

		return $this->where($column, 'IN', $value, $link);
	}

	public function whereOrIn($column, $value) {
		return $this->whereIn($column, $value, 'OR');
	}

	public function whereLike($column, $value = null, $link = 'AND') {
		return $this->where($column, "Like", $value, $link);
	}

	public function whereOrLike($column, $value) {
		return $this->whereLike($column, $value, 'OR');
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

		return $this;
	}

	public function freshSelect() {
		$this->sql->remove('select');

		return $this;
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

	public function paginate($perpage = 10, $page = 1) {
		$page = request('page', $page);
		$count = $this->count();
		$paginate = new Paginate($count, $perpage, $page);

		$result = $this->freshSelect()->limit($perpage, ($page - 1) * $perpage)->get();

		if ($result instanceof Collection) {
			$result->setPaginate($paginate->render());
		}

		return $result;
	}

	public function get($columns = ['*']) {
		if (!$this->sql->has('select')) {
			$this->select($columns);
		}

		if ($this->isSoftDelete()) {
			$this->where('deleted_at', '=', '0000-00-00 00:00:00');
		}

		if (method_exists($this->model, 'beforeGet')) {
			$this->model->beforeGet();
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
			if (($e->getCode() === 42 || $e->getCode() === '42S02') && method_exists($this->model, 'schema')) {
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

		if (method_exists($this->model, 'beforeGet')) {
			$this->model->beforeGet();
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
			if (($e->getCode() === 42 || $e->getCode() === '42S02') && method_exists($this->model, 'schema')) {
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

		try {
			$this->connection
				->query($this->getQueryString())
				->execute($this->parameters);

			if ($fetch = $this->connection->fetch(\PDO::FETCH_OBJ)) {
				return $fetch->count;
			} else {
				return false;
			}
		} catch (\PDOException $e) {
			if (($e->getCode() === 42 || $e->getCode() === '42S02') && method_exists($this->model, 'schema')) {
				$schema = new Schema($this->table);
				call_user_func([$this->model, 'schema'], $schema);
				$this->connection->query($schema->create())->execute();

				return $this->count($columns);
			}

			throw $e;
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
			$collection = new collection();
			$class = get_class($this->model);

			foreach ($fetch as $item) {
				$instance = new $class((array) $item);

				foreach ($this->getRelations()->keys() as $relationName) {
					$instance->with($relationName);
				}

				$collection->push($instance->id, $instance);
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
			$result = $this->connection
				->query($this->getQueryString('insert', $this->getProperties(true)))
				->execute($this->getProperties(true));

			if ($id = $this->connection->lastInsertId()) {
				$this->getProperties(true)->set('id', $id);
				$this->applyToModel($this->getProperties(true)->toArray());

				return $result;
			}
		} catch (\PDOException $e) {
			if (($e->getCode() === 42 || $e->getCode() === '42S02') && method_exists($this->model, 'schema')) {
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

		$this->freshWhere();
		$this->where('id', $this->model->id);
		$this->setTimestamp('update');

		return $this->connection
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
			$this->writeLog();

			return $this->update();
		}

		if ($result = $this->connection
			->query($this->getQueryString('delete'))
			->execute($this->parameters)) {

			$this->writeLog();

			return $result;
		}

	}

	public function create($array) {
		foreach (array_keys($array) as $key) {
			if ($this->isGuard() && in_array($key, $this->getGuarded())) {
				throw new \RuntimeException('The parameter {' . $key . '} has been guarded, please check');
			}

			if (!in_array($key, $this->getFill())) {
				throw new \RuntimeException('Can\'t create ' . $this->getModelName() . ' with {' . $key . '}');
			}

			if ($this->isGuard() && $key === $this->getPrimaryKey()) {
				throw new \RuntimeException('Can\'t create ' . $this->getModelName() . ' with the primary key {id}');
			}
		}

		$this->setProperties($array);

		$this->save();

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