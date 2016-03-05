<?php
namespace Chestnut\Auth;

use Chestnut\Support\Register;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class AuthRegister extends Register {
	public function register() {
		$this->app->singleton([Auth::class => 'auth']);
		$this->app->registerAlias('Auth', AuthStatic::class);
	}
}