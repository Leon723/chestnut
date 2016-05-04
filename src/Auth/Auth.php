<?php
namespace Chestnut\Auth;

use Chestnut\Contract\Support\Container as ContainerContract;
use Firebase\JWT\JWT;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Auth {
	protected $app;
	protected $isLogin = false;
	protected $user;
	protected $permissions;
	protected $model;

	public function __construct(ContainerContract $app) {
		$this->app = $app;

		$this->model = $app->config->get('auth.model', 'Model\Auth');
	}

	public function login($account, $password, $remember) {
		if ($user = $this->getModel()->where('user_name', $account)
			->orWhere('email', $account)
			->orWhere('phone', $account)
			->one()) {
			if (password_verify($password, $user->password)) {
				$this->boot($user);

				return AuthStatic::ACCOUNT_LOGIN_ACCESS;
			} else {
				return AuthStatic::WRONG_PASSWORD;
			}
		} else {
			return AuthStatic::ACCOUNT_NOT_FOUND;
		}
	}

	public function logout() {
		cookie_remove('jwt');

		return true;
	}

	public function boot($user) {
		if (!is_object($user)) {
			$user = $this->getModel()->one($user);
		}

		if (!$user) {
			return false;
		}

		$this->user = $user;
		$this->setPermissions($user->getPermissions());

		return $this->setLogin(true);
	}

	public function check($token) {
		if (is_null($token)) {
			return false;
		}

		if ($token = $this->decodeJsonWebToken($token)) {
			if (time() > $token->expire) {
				cookie_remove('jwt');
				return false;
			}

			if ($token->aud !== $this->app->config->get('app.domain')) {
				return false;
			}

			return $this->boot($token->iss);
		} else {
			return false;
		}
	}

	public function create($user) {
		if ($this->getModel()->where('user_name', $user['phone'])
			->orWhere('email', $user['phone'])
			->orWhere('phone', $user['phone'])
			->count()) {
			return AuthStatic::ACCOUNT_HAS_BEEN_REGISTERED;
		}

		unset($user['repassword']);

		$user['password'] = $this->convertPassword($user['password']);

		$id = $this->getModel()->create($user);

		$this->boot($id);

		return AuthStatic::ACCOUNT_REGISTER_SUSSECC;
	}

	public function getModel() {
		return (new $this->model)->with('role', 'brand', 'member');
	}

	public function getUser() {
		return $this->user;
	}

	public function setPermissions($permissions) {
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
		if (reset($this->permissions) == 'all') {
			return 'admin.dashboard';
		}

		return reset($this->permissions);
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

	public function setLogin($isLogin) {
		if ($this->isLogin) {
			return true;
		}

		return $this->isLogin = $isLogin;
	}

	public function generateJsonWebToken() {
		$key = $this->app->config->get('app.app_key');
		$token = [
			'iss' => $this->getId(),
			'aud' => config('app.domain'),
			'expire' => time() + 604800,
		];

		return JWT::encode($token, $key);
	}

	public function __call($method, $params) {
		return call_user_func_array([$this->user, $method], $params);
	}

	private function convertPassword($password) {
		return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
	}

	private function decodeJsonWebToken($token) {
		$key = $this->app->config->get('app.app_key');

		return JWT::decode($token, $key, ['HS256']);
	}
}
