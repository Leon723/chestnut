<?php
namespace Chestnut\Component;

abstract class Middleware {

	public function call($request) {
		return $this->handler($request);
	}

	public function register() {
		app()->registerMiddleware($this);
	}
}