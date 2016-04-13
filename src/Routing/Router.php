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

	protected $supportMethod = ['get', 'post', 'delete', 'put', 'patch', 'option', 'trace', 'any'];

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

	public function url($routeName, $method = 'get') {
		$params = [];

		if (!in_array(strtolower($method), $this->supportMethod)) {
			$params[] = $method;
			$method = 'get';
		}

		if (func_num_args() > 2) {
			$args = func_get_args();

			$params = array_merge($params, array_slice($args, 2));
		}

		if ($this->routes->hasRoute($routeName)) {
			$route = $this->routes->getRoute($routeName);
			$method = strtoupper($method);

			if (!isset($route[$method])) {
				throw new UndefinedRouteException("Undefined [{$routeName}] Route with [{$method}] method");
			}

			if ($url = $route[$method]->identifierMatch($params)) {
				return 'http://' . config('app.domain', '') . $url;
			}

			throw new InvalidArgumentException("Missing parameter in [{$routeName}] Route");
		}
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->routes, $method], $params);
	}

	public static function __callstatic($method, $params) {
		return call_user_func_array([app('route'), $method], $params);
	}
}
