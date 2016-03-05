<?php
namespace Chestnut\Auth\Model;

use Chestnut\Database\Model;
use Chestnut\Database\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Module extends Model {
	protected $fill = [
		'parent_id',
		'module_name',
		'pattern',
		'prefix',
		'middleware',
		'namespace',
		'controller',
		'method',
		'isShow',
	];

	protected $relation = [
		'sub' => [
			'many', 'id', 'parent_id',
		],
	];

	public function schema(Schema $table) {
		$table->increment('id');
		$table->string('parent_id');
		$table->string('module_name');
		$table->string('pattern');
		$table->string('prefix');
		$table->string('middleware');
		$table->string('namespace');
		$table->string('controller');
		$table->string('method');
		$table->timeStamp();

		$table->unique('module_name');
	}
}