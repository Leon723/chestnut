<?php
namespace Chestnut\Foundation\Boot;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
abstract class Boot {
	protected $app;

	public function __construct($app) {
		$this->app = $app;
	}

	abstract function boot();
}