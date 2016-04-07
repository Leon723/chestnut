<?php
namespace Chestnut\Foundation\Boot;

/**
 *  @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class initEvent extends Boot {
	public function boot() {
		$app = $this->app;

		$app->event->listen('init.register', $app, 'registerServices');
		$app->event->listen('init.resolveAlias', $app, 'resolveAlias');
		$app->event->listen('init.sessionStart', $app->session, 'start');

		$app->event->listen('boot.booting', $app, 'booting');
		$app->event->listen('boot.boot', $app, 'boot');
		$app->event->listen('boot.booted', $app, 'booted');
	}
}