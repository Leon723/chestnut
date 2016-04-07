<?php
namespace Chestnut\Database;

use Chestnut\Support\Statique;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class QueryStatic extends Statique {
	public static function getStatique() {
		return 'db.query';
	}
}