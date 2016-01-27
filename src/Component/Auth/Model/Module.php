<?php
namespace Chestnut\Component\Auth\Model;

use Model;
use Schema;

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
	];

	protected $relation = [
		'sub' => [
			'many', 'parent_id', 'id',
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