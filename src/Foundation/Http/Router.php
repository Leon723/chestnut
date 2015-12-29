<?php namespace Chestnut\Foundation\Http;

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
			if ($route->match($method, $uri)) {
				return $route;
			}
		}
	}

	public function getRoutes() {
		return $this->routes->all();
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->routes, $method], $params);
	}

	public static function __callstatic($method, $params) {
		return call_user_func_array([app('route'), $method], $params);
	}
}
