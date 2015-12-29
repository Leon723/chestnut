<?php
namespace Chestnut\Foundation\Http;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class Session extends SymfonySession {
	public function __construct() {
		$handle = new NativeFileSessionHandler(cache_path('session/'));
		$storage = new NativeSessionStorage([
			'use_cookies' => 0,
		], $handle);

		parent::__construct($storage);
	}

	public function start() {
		if ($session_id = cookie('chestnut_session')) {
			$this->setId($session_id);
			return parent::start();
		}

		parent::start();
		$session_id = $this->getId();

		cookie('chestnut_session', $session_id, 86400, true);
	}

	public static function __callStatic($method, $params) {
		return call_user_func_array([app('session', $method), $params]);
	}
}