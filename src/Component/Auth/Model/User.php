<?php
namespace Chestnut\Component\Auth\Model;

class User extends \Model {
	protected $fill = [
		'user_name',
		'phone',
		'email',
		'password',
		'salt',
	];

	public function schema(\Schema $table) {
		$table->increment('id');
		$table->string('user_name', 32, true);
		$table->string('email', true);
		$table->string('phone', 11, true);
		$table->string('password', 64);
		$table->string('salt');
		$table->string('remember_token', true);
		$table->string('permissions', true);
		$table->timeStamp();
		$table->unique('user_name', 'phone', 'email');
	}
}
