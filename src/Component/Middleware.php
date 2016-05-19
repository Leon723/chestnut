<?php
namespace Chestnut\Component;

abstract class Middleware {
	protected $app;

	public function __construct($app) {
		$this->app = $app;
	}

	public function call($request) {
		return $this->handler($request);
	}

	public function register() {
		$this->app->registerMiddleware($this);
	}
}
