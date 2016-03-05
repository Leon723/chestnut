<?php
namespace Chestnut\Auth\Model;

use Chestnut\Database\Model;
use Chestnut\Database\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class User extends Model {
	protected $fill = [
		'user_name',
		'phone',
		'email',
		'password',
		'salt',
	];

	protected $relation = [
		'role' => [
			'one', 'role', '', Role::class,
		],
	];

	public function schema(Schema $table) {
		$table->increment('id');
		$table->string('user_name', 32, true);
		$table->string('email', true);
		$table->string('phone', 11, true);
		$table->string('password', 64);
		$table->string('salt');
		$table->string('remember_token', true);
		$table->string('permissions', true);
		$table->timeStamp();
		$table->unique('phone', 'email');
	}
}
