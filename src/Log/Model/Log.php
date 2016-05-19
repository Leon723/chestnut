<?php
namespace Chestnut\Log\Model;

use Chestnut\Database\Nut\Model;
use Chestnut\Database\Schema\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Log extends Model {
	protected $fill = [
		'user_id',
		'module',
		'log_content',
		'operation',
	];

	public $withoutLog = true;

	public function schema(Schema $table) {
		$table->increment('id');
		$table->string('user_id');
		$table->string('module');
		$table->string('operation');
		$table->log_content('text');
		$table->timeStamps();
	}
}
