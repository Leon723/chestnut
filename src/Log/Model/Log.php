<?php
namespace Chestnut\Log\Model;

use Chestnut\Database\Model;
use Chestnut\Database\Schema;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Log extends Model {
	protected $fill = [
		'user_id',
		'module',
		'log_content',
	];

	protected $withoutLog = true;

	public function schema(Schema $table) {
		$table->increment('id');
		$table->string('user_id');
		$table->string('module');
		$table->text('log_content');
		$table->timeStamp();
	}
}