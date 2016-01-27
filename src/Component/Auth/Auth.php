<?php
namespace Chestnut\Component\Auth;

use Chestnut\Support\Traits\StaticizeTrait;

class Auth {

	use StaticizeTrait;

	const ACCOUNT_LOGIN_ACCESS = 8;
	const ACCOUNT_NOT_FOUND = 9;
	const WRONG_PASSWORD = 10;
	const ACCOUNT_HAS_BEEN_REGISTERED = 11;
	const ACCOUNT_REGISTER_SUSSECC = 12;
	const PERMISSION_DENIED = 13;

	protected $isLogin = false;
	protected $user;
	protected $permissions;

	public function _login($account, $password, $remember) {
		if ($this->_check()) {
			return true;
		}

		if ($user = Model\User::where('user_name', $account)
			->whereOr('email', $account)
			->whereOr('phone', $account)
			->one()) {
			if ($user->password === $this->convertPassword(decrypt($user->salt), $password)) {
				if ($remember) {
					session()->migrate(86400);
					$user->remember_token = $this->createRememberToken($account);
					$user->save();

				} else {
					session()->migrate(0);
					$user->remember_token = '';
					$user->save();
				}

				session('auth', $user);

				$this->boot($user);

				return static::ACCOUNT_LOGIN_ACCESS;
			} else {
				return static::WRONG_PASSWORD;
			}
		} else {
			return static::ACCOUNT_NOT_FOUND;
		}
	}

	private function boot($user) {
		$this->user = $user;
		$this->setPermissions($user->permissions);
		$this->addNameToViewGlobal($user->user_name);
		return $this->setLogin(true);
	}

	public function _check() {
		if (!$this->isLogin && !session('auth')) {
			return false;
		}

		$user = session('auth');

		if (app()->resolved('current')) {
			if ($this->boot($user) && $this->_hasPermission(app('current')->getIdentifier())) {
				return true;
			} else {
				// return static::PERMISSION_DENIED;
				return false;
			}
		} else {
			return $this->boot($user);
		}
	}

	public function addNameToViewGlobal($name) {
		\View::addGlobal('__user_name', $name);
	}

	public function setPermissions($permissions) {
		if (!is_array($permissions)) {
			$permissions = func_get_args();
		}

		if (strpos($permissions[0], ',')) {
			$permissions = explode(',', $permissions[0]);
		}

		foreach ($permissions as $permission) {
			$this->permissions[trim($permission)] = true;
		}
	}

	public function _hasPermission($permission) {
		if (explode('.', $permission)[0] !== 'admin') {
			return true;
		}

		if (isset($this->permissions['all']) && $this->permissions['all']) {
			return true;
		}

		return isset($this->permissions[$permission]) && $this->permissions[$permission];
	}

	public function _logout() {

		if ($this->_check()) {
			session()->remove('auth');
			$this->setLogin(false);
		}

		return true;
	}

	public function _create($user) {
		if (Model\User::where('user_name', $user['phone'])
			->whereOr('email', $user['phone'])
			->whereOr('phone', $user['phone'])
			->count() > 0) {
			return static::ACCOUNT_HAS_BEEN_REGISTERED;
		}

		unset($user['repassword']);

		$user['salt'] = $this->createSalt();
		$user['password'] = $this->convertPassword($user['salt'], $user['password']);

		$user['salt'] = encrypt($user['salt']);

		Model\User::create($user);

		return static::ACCOUNT_REGISTER_SUSSECC;
	}

	public function _newModule($array) {
		Model\Module::create($array);

		return true;
	}

	public function setLogin($isLogin) {
		if ($this->isLogin) {
			return true;
		}

		return $this->isLogin = $isLogin;
	}

	private function createSalt($length = 8) {
		$salt = '';

		for ($i = 0; $i < $length; $i++) {
			$salt .= chr(mt_rand(33, 126));
		}

		return $salt;
	}

	private function convertPassword($salt, $password) {
		$index = round(ord($salt) / 21);
		$start = substr($password, 0, $index);
		$end = substr($password, $index);

		return hash('sha256', $start . $salt . $end);
	}

	private function createRememberToken($account) {
		$session_id = session()->getId();
		$ip = request()->getClientIp();

		return hash('sha256', $ip . $session_id . $account);
	}

	public static function getStaticizer() {
		return 'auth';
	}
}