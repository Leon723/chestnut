<?php
namespace Chestnut\Database\Nut;

use Chestnut\Database\Query\Query;
use DB;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class NutQuery {
	protected $query;
	protected $model;
	protected $with;

	protected $pass = [
		'insert', 'update', 'delete', 'count', 'lastQueryString',
		'getWhere', 'getBinds',
	];

	public function __construct(Query $query) {
		$this->query = $query;
	}

	public function bindModel($model) {
		$this->model = $model;
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
		return $this->take(1)->get($columns)->first();
	}

	public function one($id = null, $columns = ['*']) {
		if (!is_null($id)) {
			$this->wherePrimary($id);
		}

		return $this->first($columns);
	}

	public function get($columns = ['*']) {
		$models = $this->getModels($columns);

		if (isset($this->with)) {
			foreach ($models as $model) {
				$model->processRelations($this->with);
			}
		}

		return $models;
	}

	public function insert($insert = null) {
		if (is_null($insert)) {
			$insert = $this->model->getProperties();
		}

		$this->model->fireEvent('beforeSave');

		$result = $this->query->insert($insert);

		$this->model->fireEvent('afterSave');

		return $result;
	}

	public function create($create = null) {
		if (is_null($create)) {
			return false;
		}

		return $this->insert($create);
	}

	public function update($id = null, $update = null) {
		if (is_null($id)) {
			$id = $this->model->getPrimary();
		}

		$this->wherePrimary($id);

		$this->model->fireEvent('beforeSave');

		if (is_null($update)) {
			$update = $this->model->getDirty();
		}

		$result = $this->query->update($update);

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

		return $this->query->delete();
	}

	public function count() {
		$this->select(DB::raw('count(*) as count'));

		$count = $this->query->get();
		$count = reset($count);

		return $count->count;
	}

	public function wherePrimary($value) {
		$this->where($this->model->getPrimaryKey(), $value);

		return $this;
	}

	public function wherePrimaries($values) {
		$this->whereIn($this->model->getPrimaryKey(), $values);

		return $this;
	}

	public function getModels($columns = ['*']) {
		$this->model->fireEvent('beforeGet');

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

		$count = $this->model->newQuery();
		$count = $this->injectWhere($count)->count();

		return new Paginate($collection, $count, $perpage, $page);
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
