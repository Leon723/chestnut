<?php
namespace Chestnut\Support;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
abstract class Statique {
	protected static $resolvedInstance;

	protected static function resolveStatique($name) {
		if (is_object($name)) {
			return $name;
		}

		if (isset(static::$resolvedInstance[$name])) {
			return static::$resolvedInstance[$name];
		}

		return static::$resolvedInstance[$name] = Container::getInstance()->make($name);
	}

	public static function __callStatic($method, $args) {
		$statique = static::resolveStatique(static::getStatique());

		switch (count($args)) {
		case 0:
			return $statique->$method();
			break;
		case 1:
			return $statique->$method($args[0]);
			break;
		case 2:
			return $statique->$method($args[0], $args[1]);
			break;
		case 3:
			return $statique->$method($args[0], $args[1], $args[2]);
			break;
		case 4:
			return $statique->$method($args[0], $args[1], $args[2], $args[3]);
			break;
		default:
			return call_user_func_array([$statique, $method], $args);
			break;
		}
	}
}