<?php
namespace Chestnut\Support\Traits;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
trait StaticizeTrait {
	public static function __callStatic($method, $params) {
		$component_name = static::getStaticizer();
		$instance = app($component_name);

		return call_user_func_array([$instance, "_$method"], $params);
	}
}