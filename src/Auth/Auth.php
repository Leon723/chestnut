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
		return (new $this->model)->with('role', 'brand', 'member');
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

				return AuthStatic::ACCOUNT_LOGIN_ACCESS;
			} else {
				return AuthStatic::WRONG_PASSWORD;
			}
		} else {
			return AuthStatic::ACCOUNT_NOT_FOUND;
		}
	}

	public function boot($user) {
		if (!is_object($user)) {
			$user = $this->getModel()->one($user);
		}

		if (!$user) {
			return false;
		}

		$this->user = $user;
		$this->setPermissions($user->permissions, $user->role->permission);
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
				return AuthStatic::PERMISSION_DENIED;
			}
		} else {
			return $this->boot($user_id);
		}
	}

	public function addNameToViewGlobal($name) {
		\View::addGlobal('__user_name', $name);
	}

	public function setPermissions($user, $role) {
		if (!is_array($user)) {
			$user = explode(',', $user);
		}

		$permissions = array_merge($user, $role);
		$this->permissions = array_filter($permissions);
	}

	public function hasPermission($permission) {
		if (in_array('all', $this->permissions)) {
			return true;
		}

		if (!in_array(explode('.', $permission)[0], ['admin', 'upload', 'all', 'show'])) {
			return true;
		}

		return in_array($permission, $this->permissions);
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
			return AuthStatic::ACCOUNT_HAS_BEEN_REGISTERED;
		}

		unset($user['repassword']);

		$user['salt'] = $this->createSalt();
		$user['password'] = $this->convertPassword($user['salt'], $user['password']);

		$user['salt'] = encrypt($user['salt']);

		$id = $this->getModel()->create($user);

		$this->boot($id);

		return AuthStatic::ACCOUNT_REGISTER_SUSSECC;
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
