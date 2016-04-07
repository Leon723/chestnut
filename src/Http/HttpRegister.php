<?php
namespace Chestnut\Http;

use Chestnut\Support\Register;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class HttpRegister extends Register {
	public function register() {
		$this->registerRequest();

		$this->registerResponse();

		$this->registerSession();

		$this->registerCookie();
	}

	private function registerRequest() {
		$this->app->singleton([Request::class => 'request'], function () {
			if (config('app.override_method', true)) {
				Request::enableHttpMethodParameterOverride();
			}

			return Request::createFromGlobals();
		});
	}

	private function registerResponse() {
		$this->app->singleton([Response::class => 'response']);
	}

	private function registerSession() {
		$this->app->singleton([Session::class => 'session']);

		$this->app->registerAlias('Session', Session::class);
	}

	private function registerCookie() {
		$this->app->register(['Symfony\Component\HttpFoundation\Cookie' => 'cookie']);
	}
}