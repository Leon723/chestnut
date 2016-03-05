<?php
namespace Chestnut\View;

use Chestnut\Support\Statique;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class ViewStatic extends Statique {
	public static function getStatique() {
		return 'view';
	}
}