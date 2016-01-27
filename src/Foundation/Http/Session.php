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
			parent::start();
		} else {
			parent::start();
			$session_id = $this->getId();

			cookie('chestnut_session', $session_id, 0);
		}

		if (request()->server->has('HTTP_REFERER') && request()->method() !== 'POST' && !session()->has('referer.' . request()->path())) {
			session('referer.' . request()->path(), request()->server->get('HTTP_REFERER'));
		}

	}

	public function migrate($destroy = false, $lifetime = 86400) {
		parent::migrate($destroy, null);

		$session_id = $this->getId();

		cookie_remove('chestnut_session');
		cookie('chestnut_session', $session_id, $lifetime);
	}

	public static function __callStatic($method, $params) {
		return call_user_func_array([app('session', $method), $params]);
	}
}