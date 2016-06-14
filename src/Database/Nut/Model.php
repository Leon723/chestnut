<?php
namespace Chestnut\Database\Nut;

use Chestnut\Events\Event;
use Closure;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
abstract class Model {
	protected $primaryKey = 'id';

	protected $exists = false;

	protected $table;

	protected $origin;

	protected $properties;

	protected $dirty;

	protected $relations;

	protected $guarded = ['created_at', 'updated_at'];

	protected $guard = true;

	public $timestamp = true;

	protected $softDelete = false;

	protected $fill = [];

	protected $hidden;

	protected static $driver;

	protected static $event;

	protected static $container;

	public function __construct(Array $properties = []) {
		$this->fill($properties);

		if (method_exists($this, 'boot')) {
			$this->boot();
		}
	}

	public function fill($properties) {
		$this->properties = new Collection($properties);
		$this->origin = new Collection($properties);
	}

	public function setExists($exists) {
		$this->exists = $exists;
	}

	public function isExists() {
		return $this->exists;
	}

	public function newInstance(Array $properties = [], $exists = false) {
		if (empty($properties)) {
			return false;
		}

		$model = new static($properties);

		$model->setExists($exists);

		if ($exists) {
			$model->fireEvent('afterGet');
		}

		return $model;
	}

	public function injectCollection($items) {
		$models = array_map(function ($model) {
			if ($result = $this->newInstance((array) $model, true)) {
				return $result;
			}
		}, $items);

		return new Collection($models);
	}

	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	public function getPrimary() {
		return $this->{$this->primaryKey};
	}

	public function getDirty() {
		return $this->dirty->toArray();
	}

	public function isSoftDelete() {
		return $this->softDelete;
	}

	public function getRelation($key) {
		return isset($this->relation[$key]) ? $this->relation[$key] : false;
	}

	public function hasRelation($key) {
		return isset($this->relation[$key]);
	}

	public function processRelations($withs) {
		if (!$this->relations) {
			$this->relations = new Collection;
		}

		if (is_string($withs)) {
			$withs = func_get_args();
		}

		foreach ($withs as $with => $callback) {
			if (!$callback instanceof Closure) {
				$with = $callback;
				$callback = null;
			}

			list($name, $relation) = $this->processRelation($with, $callback);

			$this->relations->set($name, $relation);
		}
	}

	public function processRelation($relationName, $callback) {

		if ($relation = $this->getRelation($relationName)) {
			list($type, $local_key, $foreign_key, $model) = $relation;

			$instance = new $model;

			$instance = !empty($foreign_key)
			? $instance->where($foreign_key, $this->{$local_key})
			: $instance->wherePrimary($this->{$local_key});

			if ($callback instanceof Closure) {
				$instance = $callback($instance);
			}

			if ($instance) {
				switch ($type) {
				case 'many':
					$result = $instance->get();
					break;
				case 'one':
					$result = $instance->one();
					break;
				case 'count':
					$result = $instance->count();
					break;
				}

				return [$relationName, $result];
			}
		}

		return [$relationName, false];
	}

	public function withDeleted() {
		$this->softDelete = false;
	}

	public function getClass() {
		return get_class($this);
	}

	public function getTable() {
		$class = explode('\\', $this->getClass());
		$modelName = array_pop($class);

		return $this->table ? $this->table : to_underline($modelName);
	}

	public function getFillable() {
		return $this->fill ? $this->fill : [];
	}

	public function registerEvent($event, $method = null) {
		if (is_null($method)) {
			$method = $event;
		}

		$hash_id = spl_object_hash($this);

		static::$event->listen($this->getClass() . ".{$event}.{$hash_id}", $this, $method);
	}

	public function fireEvent($event, $params = []) {
		$hash_id = spl_object_hash($this);

		return static::$event->fire($this->getClass() . '.' . $event . ".{$hash_id}", $params);
	}

	public function newQuery() {
		$query = static::$container->make('db.query', static::$driver)->from($this->getTable());

		$builder = new NutQuery($query);
		$builder->bindModel($this);

		return $builder;
	}

	public function getProperties() {
		if (!$this->guard) {
			return $this->properties->toArray();
		}

		return $this->properties->filter([$this->getPrimaryKey()])->toArray();
	}

	public function setDirty($key, $value) {
		if (!isset($this->dirty)) {
			$this->dirty = new Collection;
		}

		if ($this->$key !== $this->origin->get($key) || $this->dirty->has($key)) {
			$this->dirty->set($key, $value);
		}
	}

	public function __get($key) {
		if ($this->relations && $this->relations->has($key)) {
			return $this->relations->get($key);
		}

		return $this->properties->get($key);
	}

	public function __set($key, $value) {
		$this->properties->set($key, $value);
		$this->setDirty($key, $value);
	}

	public function __unset($key) {
		$this->properties->remove($key);

		if ($this->dirty) {
			$this->dirty->remove($key);
		}
	}

	public function __isset($key) {
		if (isset($this->relations[$key])) {
			return true;
		}

		return $this->properties->has($key);
	}

	public function toArray() {
		return $this->properties->toArray();
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->newQuery(), $method], $params);
	}

	public static function __callStatic($method, $params) {
		$instance = new static;

		return call_user_func_array([$instance, $method], $params);
	}

	public static function setEvent(Event $event) {
		static::$event = $event;
	}

	public static function setContainer($container) {
		static::$container = $container;
	}
}
