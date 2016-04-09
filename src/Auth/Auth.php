<?php
namespace Chestnut\Auth;

use Chestnut\Contract\Support\Container as ContainerContract;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Auth {

	const ACCOUNT_LOGIN_ACCESS = 8;
	const ACCOUNT_NOT_FOUND = 9;
	const WRONG_PASSWORD = 10;
	const ACCOUNT_HAS_BEEN_REGISTERED = 11;
	const ACCOUNT_REGISTER_SUSSECC = 12;
	const PERMISSION_DENIED = 13;

	protected $app;
	protected $isLogin = false;
	protected $user;
	protected $permissions;
	protected $model;

	public function __construct(ContainerContract $app) {
		$this->app = $app;

		$this->model = $app->config->get('auth.model', 'Model\Auth');
	}

	public function getModel() {
		return new $this->model;
	}

	public function getUser() {
		return $this->user;
	}

	public function login($account, $password, $remember) {

		if ($this->check()) {
			return true;
		}

		if ($user = $this->getModel()->where('user_name', $account)
			->orWhere('email', $account)
			->orWhere('phone', $account)
			->with('role')
			->one()) {
			if ($user->password === $this->convertPassword(decrypt($user->salt), $password)) {
				if ($remember) {
					session()->migrate(604800);
					$user->remember_token = $this->createRememberToken($account);
				} else {
					session()->migrate(0);
					$user->remember_token = '';
				}

				$this->boot($user);
				$user->save();

				return static::ACCOUNT_LOGIN_ACCESS;
			} else {
				return static::WRONG_PASSWORD;
			}
		} else {
			return static::ACCOUNT_NOT_FOUND;
		}
	}

	private function boot($user) {
		if (!is_object($user)) {
			$user = $this->getModel()->with('member')->one($user);
		}

		if (!$user) {
			return false;
		}

		$this->user = $user;
		$this->setPermissions($user->permissions);
		$this->addNameToViewGlobal($user->user_name);
		session('auth', $user->id);
		return $this->setLogin(true);
	}

	public function check() {
		if (!$this->isLogin && !session('auth')) {
			return false;
		}

		$user_id = session('auth');

		if (app()->resolved('current')) {
			if ($this->boot($user_id) && $this->hasPermission(app('current')->getIdentifier())) {
				return true;
			} else {
				return static::PERMISSION_DENIED;
			}
		} else {
			return $this->boot($user_id);
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

		if (in_array('all', $permissions)) {
			$this->permissions['all'] = true;
			return;
		}

		if ($this->user->role) {
			$permissions = array_merge($permissions, explode(',', $this->user->role->permission));
		}

		foreach ($permissions as $permission) {
			if (!empty($permission)) {
				$this->permissions[trim($permission)] = true;
			}
		}
	}

	public function hasPermission($permission) {
		if (isset($this->permissions['all']) && $this->permissions['all']) {
			return true;
		}

		if (!in_array(explode('.', $permission)[0], ['admin', 'upload', 'all', 'show'])) {
			return true;
		}

		return isset($this->permissions[$permission]) && $this->permissions[$permission];
	}

	public function getFirstPermission() {
		if (key($this->permissions) == 'all') {
			return 'admin.dashboard';
		}

		return key($this->permissions);
	}

	public function getAccount() {
		if (!isset($this->user)) {
			return 'onekeymall';
		}

		return $this->user->phone;
	}

	public function getId() {
		if (!isset($this->user)) {
			return 0;
		}

		return $this->user->id;
	}

	public function logout() {

		if ($this->check()) {
			session()->remove('auth');
			$this->setLogin(false);
		}

		return true;
	}

	public function create($user) {
		if ($this->getModel()->where('user_name', $user['phone'])
			->orWhere('email', $user['phone'])
			->orWhere('phone', $user['phone'])
			->count()) {
			return static::ACCOUNT_HAS_BEEN_REGISTERED;
		}

		unset($user['repassword']);

		$user['salt'] = $this->createSalt();
		$user['password'] = $this->convertPassword($user['salt'], $user['password']);

		$user['salt'] = encrypt($user['salt']);

		$user = $this->getModel()->create($user);

		$this->boot($user->id);

		return static::ACCOUNT_REGISTER_SUSSECC;
	}

	public function setLogin($isLogin) {
		if ($this->isLogin) {
			return true;
		}

		return $this->isLogin = $isLogin;
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->user, $method], $params);
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
}
