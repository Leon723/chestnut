<?php
namespace Chestnut\Routing;

use Closure;
use IteratorAggregate;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
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

		if (is_array($method)) {
			foreach ($method as $m) {
				$this->routes[$route->getIdentifier()][strtoupper($m)] = $route;
			}
		} else {
			$this->routes[$route->getIdentifier()][strtoupper($method)] = $route;
		}

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

		return array_merge_recursive($group, $options);
	}

	public function hasRoute($route) {
		return isset($this->routes[$route]);
	}

	public function getRoute($route) {
		return $this->routes[$route];
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