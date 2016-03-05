<?php
namespace Chestnut\View;

use Chestnut\Support\Register;
use Chestnut\View\Engine\NutEngine;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class ViewRegister extends Register {
	public function register() {
		$this->registerView();
		$this->registerViewEngine();
	}

	private function registerView() {
		$this->app->singleton([Factory::class => 'view']);

		$this->app->registerAlias('View', ViewStatic::class);
	}

	private function registerViewEngine() {
		$this->app->register([NutEngine::class => 'view.engine.nut']);
	}
}