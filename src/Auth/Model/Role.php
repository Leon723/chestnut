<?php
namespace Chestnut\Auth\Model;

use Chestnut\Database\Model;
use Chestnut\Database\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Role extends Model {
	protected $fill = [
		'role_name',
		'permission',
	];

	public function schema(Schema $table) {
		$table->increment('id');
		$table->string('role_name');
		$table->string('permission');
		$table->timeStamp();
		$table->unique('role_name');
	}
}