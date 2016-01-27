<?php namespace Chestnut\Foundation\Http;

use Auth;
use Chestnut\Component\Auth\Model\Module;
use Chestnut\Error\Route\UndefinedRouteException;
use Chestnut\Support\Parameter;
use InvalidArgumentException;
use View;

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

	public function withNut() {
		Auth::check();
		$modules = Module::where('parent_id', 0)->with('sub')->get();

		foreach ($modules as $module) {
			$this->processModule($module);
		}
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

			if ($url = $route[$method]->identifierMatch($args)) {
				return 'http://' . config('app.domain', '') . $url;
			}

			throw new InvalidArgumentException("Missing args in [{$routeName}] Route");
		}

		throw new UndefinedRouteException("Undefine [{$routeName}] Route with [{$method}] method");
	}

	public function processModule($module) {
		$options = [
			'prefix' => $module->prefix,
			'middleware' => unserialize($module->middleware),
			'namespace' => $module->namespace,
		];

		if ($subs = $module->sub) {
			$this->group($options, function () use ($subs, $module) {
				$global = [];
				$subModule = [];
				if ($subs instanceof Parameter) {
					foreach ($subs as $sub) {
						if (in_array($sub->parent_id, [1, 2])) {
							$route = $this->processModule($sub);
							if (Auth::hasPermission($route->getIdentifier())) {
								$global[$route->getIdentifier()] = $sub->module_name;
							}
						} else {
							$sub->prefix = $module->pattern;
							$route = $this->processModule($sub);

							if (Auth::hasPermission($route->getIdentifier())) {
								$subModule[$route->getParent()][$route->getIdentifier()] = $sub->module_name;
							}
						}
					}
				} else {
					if (in_array($sub->parent_id, [1, 2])) {
						$route = $this->processModule($sub);

						if (Auth::hasPermission($route->getIdentifier())) {
							$global[$route->getIdentifier()] = $sub->module_name;
						}
					} else {
						$sub->prefix = $module->pattern;
						$route = $this->processModule($sub);

						if (Auth::hasPermission($route->getIdentifier())) {
							$subModule[$route->getParent()][$route->getIdentifier()] = $sub->module_name;
						}
					}
				}

				if ($module->id == 1) {
					View::addGlobal('__' . ltrim($module->prefix, '/') . 'Module', $global);
				}

				if ($module->parent_id == 1) {
					View::addGlobal('__subModule', $subModule);
				}

			});
		}

		$options['controller'] = $module->controller;

		return $this->{$module->method}($module->pattern, $options);
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->routes, $method], $params);
	}

	public static function __callstatic($method, $params) {
		return call_user_func_array([app('route'), $method], $params);
	}
}
