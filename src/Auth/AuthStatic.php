<?php
namespace Chestnut\Auth;

use Chestnut\Support\Statique;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class AuthStatic extends Statique {
	const ACCOUNT_LOGIN_ACCESS = 8;
	const ACCOUNT_NOT_FOUND = 9;
	const WRONG_PASSWORD = 10;
	const ACCOUNT_HAS_BEEN_REGISTERED = 11;
	const ACCOUNT_REGISTER_SUSSECC = 12;
	const PERMISSION_DENIED = 13;

	public static function getStatique() {
		return 'auth';
	}
}