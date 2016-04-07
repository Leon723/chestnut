<?php
namespace Chestnut\Routing;

use Auth;
use Chestnut\Error\Route\UndefinedRouteException;
use InvalidArgumentException;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Router {
	/**
	 * 路由存储
	 *
	 * @var \Chestnut\Foundation\Http\RouteCollector
	 */
	protected $routes;

	public function __construct() {
		$this->routes = new RouteCollector;
	}

	/**
	 * 匹配路由
	 *
	 * @return void
	 */
	public function match($method, $uri) {
		foreach ($this->getRoutes() as $index => $route) {
			if (isset($route[$method]) && $route[$method]->match($method, $uri)) {
				return $route[$method];
			}
		}
	}

	public function getRoutes() {
		return $this->routes->all();
	}

	public function url() {
		$args = func_get_args();
		$routeName = array_shift($args);
		$method = 'GET';
		if (func_num_args() > 2) {
			$method = array_shift($args);
		}

		if ($this->routes->hasRoute($routeName)) {
			$route = $this->routes->getRoute($routeName);

			if (!isset($route[$method])) {
				throw new UndefinedRouteException("Undefined [{$routeName}] Route with [{$method}] method");
			}

			if ($url = $route[$method]->identifierMatch($args)) {
				return 'http://' . config('app.domain', '') . $url;
			}

			throw new InvalidArgumentException("Missing args in [{$routeName}] Route");
		}
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->routes, $method], $params);
	}

	public static function __callstatic($method, $params) {
		return call_user_func_array([app('route'), $method], $params);
	}
}
