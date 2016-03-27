<?php
namespace Chestnut\Auth\Model;

use Chestnut\Database\Model;
use Chestnut\Database\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Auth extends Model {
	protected $fill = [
		'user_name',
		'phone',
		'email',
		'password',
		'salt',
		'role',
	];

	protected $relation = [
		'role' => [
			'one', 'role_id', '', Role::class,
		],
	];

	public function schema(Schema $table) {
		$table->increment('id');
		$table->integer('member_id');
		$table->string('wx');
		$table->string('weibo');
		$table->string('qq');
		$table->string('user_name', 32, true);
		$table->string('email', true);
		$table->string('phone', 11, true);
		$table->string('password', 64);
		$table->string('salt');
		$table->string('remember_token', true);
		$table->string('permissions', true);
		$table->tinyinteger('role_id');
		$table->timeStamp();
		$table->unique('phone', 'email', 'wx', 'weibo', 'qq');
	}
}
