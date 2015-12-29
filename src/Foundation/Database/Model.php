<?php namespace Chestnut\Foundation\Database;

abstract class Model implements \IteratorAggregate {

	protected $modelName;

	protected $primaryKey = 'id';

	protected $exist = false;

	protected $origin;

	protected $table;

	protected $properties;

	protected $query;

	protected $guarded = ['created_at', 'updated_at'];

	protected $fill = [];

	public function __construct() {
		$this->modelName = get_class($this);

		$this->query = new QueryManager($this->getTable());
		$this->query->bindModel($this);

		$this->properties = new Collection;
		$this->origin = new Collection;
	}

	public function getTable() {
		if (isset($this->table)) {
			return $this->table;
		}

		return $this->modelName;
	}

	public function getModelName($full = false) {
		if ($full) {
			return $this->modelName;
		}

		$modelName = explode("\\", $this->modelName);

		return array_pop($modelName);
	}

	public function setExist($exist) {
		$this->exist = $exist;
	}

	public function save() {
		return $this->exist ? $this->query->update($this->properties) : $this->query->insert($this->properties);
	}

	public function update() {
		return $this->query->update($this->properties);
	}

	public function getOrigin() {
		return $this->origin->filter([$this->primaryKey]);
	}

	public function setOrigin($origin) {
		$this->origin->replace($origin);
	}

	public function getProperties($full = false) {
		if ($full) {
			return $this->properties;
		}

		return $this->properties->filter([$this->primaryKey]);
	}

	public function setProperties($properties) {
		if ($this->properties->length() > 0) {
			return;
		}

		$this->properties->replace($properties);
	}

	public function getFill() {
		return $this->fill;
	}

	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	public function getGuarded() {
		return $this->guarded;
	}

	public function setTimestamp($type = 'both') {
		switch ($type) {
		case 'both':
			$this->created_at = date('Y-m-d H:i:s');
			$this->updated_at = date('Y-m-d H:i:s');
			break;
		case 'update':
			$this->updated_at = date('Y-m-d H:i:s');
			break;
		}
	}

	public function isSoftDelete() {
		return isset($this->softDelete) ? $this->softDelete : false;
	}

	public function setSoftDelete() {
		$this->deleted_at = date('Y-m-d H:i:s');
	}

	public function __get($key) {
		return $this->properties->get($key);
	}

	public function __set($key, $value) {
		$this->properties->set($key, $value);
	}

	public function getIterator() {
		return $this->properties->getIterator();
	}

	public function __call($method, $params) {
		$call = call_user_func_array([$this->query, $method], $params);

		return $call === $this->query ? $this : $call;
	}

	public static function __callStatic($method, $params) {
		$instance = new static;

		if (method_exists($instance, $method)) {
			return call_user_func_array([$instance, $method], $params);
		}

		call_user_func_array([$instance->query, $method], $params);

		return $instance;
	}
}
