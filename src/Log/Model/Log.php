<?php
namespace Chestnut\Log\Model;

use Model;
use Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Log extends Model {
	protected $fill = [
		'user_id',
		'module',
		'log_content',
	];

	public function schema(Schema $table) {
		$table->increment('id');
		$table->string('user_id');
		$table->string('module');
		$table->text('log_content');
		$table->timeStamp();
	}
}