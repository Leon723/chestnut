<?php
namespace Chestnut\Auth\Model;

use Chestnut\Database\Nut\Model;
use Chestnut\Database\Schema\Schema;

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
		$table->role_name('string');
		$table->permission('string');
		$table->timeStamps();
		$table->unique('role_name');
	}
}
