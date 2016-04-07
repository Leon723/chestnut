<?php
namespace Chestnut\Events;

use Chestnut\Support\Register;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class EventRegister extends Register {
	public function register() {
		$this->app->singleton([Event::class => 'event']);
	}
}