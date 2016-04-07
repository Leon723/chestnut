<?php
namespace Chestnut\Database;

use Chestnut\Support\Statique;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class DatabaseStatic extends Statique {
	public static function getStatique() {
		return 'db';
	}
}