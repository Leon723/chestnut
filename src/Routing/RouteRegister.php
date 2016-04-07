<?php
namespace Chestnut\Routing;

use Chestnut\Support\Register;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class RouteRegister extends Register {

	public function register() {
		$this->registerRoute();
	}

	private function registerRoute() {
		$this->app->singleton([Router::class => 'route']);
		$this->app->registerAlias('Route', Router::class);
	}
}