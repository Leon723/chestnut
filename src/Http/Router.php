<?php
namespace Chestnut\Http;

use Auth;
use Chestnut\Auth\Model\Module;
use Chestnut\Error\Route\UndefinedRouteException;
use Chestnut\Support\Parameter;
use InvalidArgumentException;
use View;

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
						if (in_array($sub->parent_id, [1, 2, 13])) {
							$route = $this->processModule($sub);
							if (Auth::hasPermission($route->getIdentifier()) && $sub->isShow) {
								$global[$route->getIdentifier()] = ['name' => $sub->module_name, 'isShow' => $sub->isShow];
							}
						} else {
							$sub->prefix = $module->pattern;
							$route = $this->processModule($sub);

							if (Auth::hasPermission($route->getIdentifier()) && $module->parent_id == 1) {
								View::addGlobal('__subModule', $route->getParent(), $route->getIdentifier(), ['name' => $sub->module_name, 'isShow' => $sub->isShow]);
							}
						}
					}
				} else {
					if (in_array($subs->parent_id, [1, 2, 13])) {
						$route = $this->processModule($subs);

						if (Auth::hasPermission($route->getIdentifier()) && $subs->isShow) {
							$global[$route->getIdentifier()] = ['name' => $subs->module_name, 'isShow' => $subs->isShow];
						}
					} else {
						$subs->prefix = $module->pattern;
						$route = $this->processModule($subs);

						if (Auth::hasPermission($route->getIdentifier()) && $module->parent_id == 1) {
							View::addGlobal('__subModule', $route->getParent(), $route->getIdentifier(), ['name' => $subs->module_name, 'isShow' => $subs->isShow]);
						}
					}
				}

				if (in_array($module->id, [1, 13])) {
					View::addGlobal('__' . ltrim($module->prefix, '/') . 'Module', $global);
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
