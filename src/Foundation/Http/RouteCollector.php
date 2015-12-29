<?php
namespace Chestnut\Foundation\Http;

use Closure;
use IteratorAggregate;

class RouteCollector implements IteratorAggregate {
	protected $routes = [];

	/**
	 * 组合队列
	 *
	 * @var array
	 */
	protected $groupStack = [];

	public function addRoute($method, $options) {
		list($pattern, $options) = $this->convertRouteOption($options);

		$route = new Route($method, $pattern, $options);

		$this->routes[] = $route;

		return $route;
	}

	public function all() {
		return $this->routes;
	}

	public function group($options, Closure $callable) {
		if ($this->hasGroupStack()) {
			$options = $this->processGroup($options);
		}

		$this->groupStack[] = $options;

		$callable();

		array_pop($this->groupStack);
	}

	public function convertRouteOption($args) {
		$pattern = array_shift($args);
		$options = array_pop($args);

		if (!is_array($options)) {
			$options = ['controller' => $options];
		}

		if (count($args) === 1) {
			$options['middleware'] = array_shift($args);
		}

		if ($this->hasGroupStack()) {
			$options = $this->processGroup($options);
		}

		return [$pattern, $options];
	}

	public function hasGroupStack() {
		return count($this->groupStack);
	}

	public function processGroup($options) {
		$group = end($this->groupStack);

		if (array_key_exists('namespace', $group) && array_key_exists('namespace', $options)) {
			$options['namespace'] = $group['namespace'] . '\\' . $options['namespace'];
		} elseif (array_key_exists('namespace', $group) && !$options['controller'] instanceof Closure) {
			$options['controller'] = $group['namespace'] . '\\' . $options['controller'];
		}

		return array_merge($group, $options);
	}

	public function getIterator() {
		return new \ArrayIterator($this->routes);
	}

	public function __call($method, $params) {
		if (!in_array($method, ['get', 'post', 'delete', 'put', 'patch', 'option', 'trace', 'any'])) {
			throw new \RuntimeException("call to an undefined method [$method] on " . static::class);
		}

		if ($method === 'get') {
			$method = ['head', 'get'];
		}

		if ($method === 'any') {
			$method = ['get', 'post', 'delete', 'put', 'patch', 'option', 'trace'];
		}

		return call_user_func_array([$this, 'addRoute'], ['method' => $method, 'options' => $params]);
	}
}