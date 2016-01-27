<?php namespace Chestnut\Support;

use ArrayAccess;
use Chestnut\Contract\Support\Container as ContainerContract;
use Chestnut\Support\ControllerBuilder;
use Chestnut\Support\Parameter;

class Container implements ContainerContract, ArrayAccess {
	protected $registry;
	protected $instances;
	protected $aliases;
	protected $buildStack = [];

	static protected $container;

	public function __construct() {
		$this->registry = new Parameter;
		$this->instances = new Parameter;
		$this->aliases = new Parameter;
	}

	/**
	 * Register component
	 *
	 * @param string              $name     name of Component
	 * @param Closure|string|null $builder  builder of Component
	 * @param bool                $share    share Component
	 *
	 * @return void
	 */
	public function register($name, $builder = null, $share = false) {
		if (is_array($name)) {
			list($name, $alias) = $this->extractName($name);

			$this->alias($alias, $name);
		}

		if (is_null($builder)) {
			$builder = $name;
		}

		$this->registry->set($name, compact('builder', 'share'));
	}

	/**
	 * Register singleton component
	 *
	 * @param string              $name     name of component
	 * @param Closure|string|null $builder  builder of Component
	 *
	 * @return void
	 */
	public function singleton($name, $builder = null) {
		$this->register($name, $builder, true);
	}

	/**
	 * Register component instance
	 *
	 * @param string  $name     name of component
	 * @param mixed   $instance instance of component
	 *
	 * @return void
	 */
	public function instance($name, $instance) {
		$this->instances->set($name, $instance);
	}

	/**
	 * Resolve Component
	 *
	 * @param string  $name       component name or alias
	 * @param array   $parameters component parameter
	 *
	 * @return mixed
	 */
	public function make($name, $parameters = []) {
		$name = $this->getAlias($name);

		if ($this->resolved($name)) {
			return $this->instances->get($name);
		}

		$this->buildStack[] = $name;

		$builder = $this->getBuilder($name);

		$component = $this->build($builder, $parameters);

		if ($this->isShared($name)) {
			$this->instances->set($name, $component);
		}

		array_pop($this->buildStack);

		return $component;
	}

	/**
	 * Build Component instance
	 *
	 * @param string  $builder    builder of component
	 * @param array   $parameters parameter of component
	 *
	 * @return mixed
	 */
	public function build($builder, $parameters) {
		if (is_null($builder)) {
			$builder = end($this->buildStack);
		}

		$cb = new ControllerBuilder($builder);

		$cb->inject($this, $parameters);

		$component = $cb->build();

		return $component;
	}

	/**
	 * Register component's alias
	 *
	 * @param string $alias component's alias
	 * @param string $name  component's name
	 *
	 * @return void
	 */
	public function alias($alias, $name) {
		$this->aliases->set($alias, $name);
	}

	/**
	 * get component's name by alias
	 *
	 * @param string $alias component's alias
	 *
	 * @return string
	 */
	public function getAlias($alias) {
		return $this->aliases->get($alias, $alias);
	}

	/**
	 * Remove component
	 *
	 * @param string $name component's name
	 */
	public function remove($name) {
		if ($this->isAlias($name)) {
			$alias = $name;
			$name = $this->getAlias($name);

			$this->aliases->remove($alias);
		}

		if ($this->resolved($name)) {
			$this->instances->remove($name);
		}

		if ($this->registered($name)) {
			$this->registry->remove($name);
		}
	}

	/**
	 * Determine component is registered
	 *
	 * @param string $name component's name
	 *
	 * @return boolean
	 */
	public function registered($name) {
		$name = $this->getAlias($name);

		return $this->registry->has($name) || $this->instances->has($name);
	}

	/**
	 * Determine component is resolved
	 *
	 * @param string $name component's name
	 *
	 * @return boolean
	 */
	public function resolved($name) {
		$name = $this->getAlias($name);

		return $this->instances->has($name);
	}

	/**
	 * Determine component has alias
	 *
	 * @param string $alias component's alias
	 *
	 * @return boolean
	 */
	public function isAlias($name) {
		return $this->aliases->has($name);
	}

	/**
	 * Determine component is share
	 *
	 * @param string $name component's name
	 *
	 * @return boolean
	 */
	public function isShared($name) {
		$name = $this->getAlias($name);

		return $this->registry->get($name . '.share');
	}

	public function getBuilder($name) {
		return $this->registry->get($name . '.builder');
	}

	public function extractName($name) {
		return [key($name), current($name)];
	}

	public function getBuildStack() {
		return $this->buildStack;
	}

	public function offsetGet($key) {
		return $this->make($key);
	}

	public function offsetSet($key, $value) {
		$this->instance($key, $value);
	}

	public function offsetExists($key) {
		return $this->registered($key) || $this->resolved($key);
	}

	public function offsetUnset($key) {
		$this->remove($key);
	}

	public function __get($key) {
		return $this[$key];
	}

	public function __set($key, $value) {
		$this[$key] = $value;
	}

	public static function setInstance(ContainerContract $container) {
		static::$container = $container;
	}

	public static function getInstance() {
		return static::$container;
	}
}
