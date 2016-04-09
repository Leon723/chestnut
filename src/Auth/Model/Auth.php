<?php
namespace Chestnut\Auth\Model;

use Chestnut\Database\Nut\Model;
use Chestnut\Database\Schema\Schema;

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
		$table->member_id('integer');
		$table->wx('string');
		$table->weibo('string');
		$table->qq('string');
		$table->user_name('string', 32, true);
		$table->email('string', true);
		$table->phone('string', 11, true);
		$table->password('string', 64);
		$table->salt('string');
		$table->remember_token('string', true);
		$table->permissions('string', true);
		$table->role_id('tinyinteger');
		$table->timeStamp();

		$table->unique('phone', 'email', 'wx', 'weibo', 'qq');
	}
}
