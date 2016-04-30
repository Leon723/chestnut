<?php
namespace Chestnut\Database\Nut;

use Chestnut\Database\Query\Query;
use Chestnut\Database\Schema\MysqlSchema;
use DB;
use InvalidArgumentException;
use Log;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class NutQuery {
	protected $query;
	protected $model;
	protected $with;

	protected $pass = [
		'insert', 'update', 'delete', 'count', 'lastQueryString',
		'getWhere', 'getBinds', 'isTableExists',
	];

	public function __construct(Query $query) {
		$this->query = $query;
	}

	public function tableExistsOrCreate() {
		if ($this->isTableExists()) {
			return true;
		}

		if (method_exists($this->model, 'schema')) {
			$schema = $this->getSchema();

			$this->model->schema($schema);

			$this->createTable((string) $schema);
		}
	}

	public function getSchema() {
		switch ($this->query->getDriver()) {
		case 'mysql':
			return new MysqlSchema($this->query->getTable());
		}
	}

	public function bindModel($model) {
		$this->model = $model;

		$this->tableExistsOrCreate();
	}

	public function find($id, $columns = ['*']) {
		if (is_array($id)) {
			return $this->findMany($id, $columns);
		}

		$this->wherePrimary($id);

		return $this->first($columns);
	}

	public function findMany($ids, $columns = ['*']) {
		$this->wherePrimaries($ids);

		return $this->get($columns);
	}

	public function first($columns = ['*']) {
		$get = $this->take(1)->get($columns);
		return $get ? $get->first() : $get;
	}

	public function one($id = null, $columns = ['*']) {
		if (!is_null($id)) {
			$this->wherePrimary($id);
		}

		return $this->first($columns);
	}

	public function get($columns = ['*']) {
		$models = $this->getModels($columns);

		if (!$models->count()) {
			return false;
		}

		if (isset($this->with)) {
			foreach ($models as $model) {
				$model->processRelations($this->with);
			}
		}

		return $models;
	}

	private function insert() {
		$this->model->created_at = date('Y-m-d H:i:s');

		$this->model->fireEvent('beforeSave');

		$insert = $this->model->getProperties();

		$result = $this->query->insert($insert);

		if (!$this->model->withoutLog) {
			Log::write($insert);
		}

		$this->model->fireEvent('afterSave');

		$this->model->setExists(true);

		if ($id = $this->query->getLastInsertId()) {
			$this->model->{$this->model->getPrimaryKey} = $id;
		}

		return isset($id) ? $id : $result;
	}

	public function create($create = null) {
		if (is_null($create)) {
			return false;
		}

		array_walk($create, function ($val, $key, $fillable) {
			if (!in_array($key, $fillable)) {
				throw new InvalidArgumentException("Can't create {$this->model->getClass()} with column: '{$key}'");
			}
		}, $this->model->getFillable());

		$instance = $this->model->newInstance($create);

		return $instance->save();
	}

	private function update($id = null, $update = null) {
		if (is_null($id)) {
			$id = $this->model->getPrimary();
		}

		$this->model->fireEvent('beforeSave');

		if (is_null($update)) {
			$update = $this->model->getDirty();
		}

		$update['updated_at'] = date('Y-m-d H:i:s');

		$this->wherePrimary($id);

		$result = $this->query->update($update);

		if (!$this->model->withoutLog) {
			Log::write($update, 'update');
		}

		$this->model->fireEvent('afterSave');

		return $result;
	}

	public function save() {
		return $this->model->isExists() ? $this->update() : $this->insert();
	}

	public function delete($id = null) {
		if (is_null($id)) {
			$id = $this->model->getPrimary();
		}

		is_array($id) ? $this->wherePrimaries($id) : $this->wherePrimary($id);

		$result = $this->query->delete();

		if (!$this->model->withoutLog) {
			Log::write(compact('id'), 'delete');
		}

		return $this->query->delete();
	}

	public function count() {
		$count = $this->model->newQuery();
		$count = $this->injectWhere($count)->select(DB::raw('count(*) as count'))->one();

		return $count->count;
	}

	public function wherePrimary($value) {
		if (is_array($value)) {
			return $this->wherePrimaries($value);
		}

		$this->where($this->model->getPrimaryKey(), $value);

		return $this;
	}

	public function wherePrimaries($values) {
		$this->whereIn($this->model->getPrimaryKey(), $values);

		return $this;
	}

	public function getModels($columns = ['*']) {
		$this->model->fireEvent('beforeGet', ['query' => $this]);

		$result = $this->query->get($columns);

		return $this->model->injectCollection($result);
	}

	public function with($with) {
		$this->with = is_string($with) ? func_get_args() : $with;

		return $this;
	}

	public function paginate($perpage = 10, $columns = ['*']) {
		$page = request('page', 0);

		$page = $page >= 1 ? $page : 1;

		$this->take($perpage);
		$this->skip(($page - 1) * $perpage);

		$collection = $this->get($columns);

		$count = $this->count();

		if ($count) {
			return new Paginate($collection, $count, $perpage, $page);
		}

		return false;

	}

	public function injectWhere($query) {
		if ($wheres = $this->getWhere()) {
			$binds = $this->getBinds();

			foreach ($wheres as $where => $config) {
				$query->where($where, $config['symbol'], array_shift($binds), $config['link']);
			}
		}

		return $query;
	}

	public function __call($method, $params) {
		$result = call_user_func_array([$this->query, $method], $params);

		return in_array($method, $this->pass) ? $result : $this;
	}
}
