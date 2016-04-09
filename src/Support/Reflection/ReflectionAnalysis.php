<?php
namespace Chestnut\Support\Reflection;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class ReflectionAnalysis {
	public static function analysis($object, $method = null) {
		if (!is_null($method) && !method_exists($object, $method)) {
			return false;
		}

		if ($object instanceof Closure) {
			return 'closure';
		}

		if (!is_null($method)) {
			return 'method';
		}

		return 'class';
	}

	public static function getDependencies($object, $method = null) {
		switch (static::analysis($object, $method)) {
		case 'closure':
			return static::analysisClosure($object);
		case 'method':
			return static::analysisMethod($object, $method);
		case 'class':
			return static::analysisClass($object);
		default:
			return false;
		}
	}

	public static function getReflector($object, $method = null) {
		switch (static::analysis($object, $method)) {
		case 'closure':
			return $object;
		case 'method':
			$object = is_string($object) ? new $object : $object;

			$reflector = new ReflectionMethod($object, $method);

			return $reflector->getClosure($object);
		default:
			return new ReflectionClass($object);
		}
	}

	public static function analysisClosure($object) {
		$reflector = new ReflectionFunction($object);

		return $reflector->getParameters();
	}

	public static function analysisMethod($object, $method) {
		$reflector = new ReflectionMethod($object, $method);

		return $reflector->getParameters();
	}

	public static function analysisClass($object) {
		$reflector = new ReflectionClass($object);

		if (is_null($reflector->getConstructor())) {
			return [];
		} else {
			return $reflector->getConstructor()->getParameters();
		}
	}
}
