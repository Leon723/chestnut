<?php
namespace Chestnut\Log;

use Chestnut\Support\Register;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class LogRegister extends Register {
	public function register() {
		// $this->app->singleton([Log::class => 'log']);
		$this->app->registerAlias('Log', Log::class);
	}
}