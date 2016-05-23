<?php
namespace Chestnut\Support;

use ArrayAccess;
use Chestnut\Contract\Support\Parameter as ParameterContract;
use Closure;
use IteratorAggregate;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Parameter implements ParameterContract, ArrayAccess, IteratorAggregate {
	protected $attributes;

	public function __construct(array $value = []) {
		$this->replace($value);
	}

	public function replace($value) {
		$this->attributes = (array) $value;
	}

	public function keys($key = null) {
		return is_null($key) ? array_keys($this->attributes) : array_keys($this->attributes[$key]);
	}

	public function set($key, $value) {
		$array = &$this->attributes;
		$keys = explode('.', $key);

		foreach ($keys as $key) {
			if (!isset($array[$key])) {
				$array[$key] = [];
			}

			if (!is_array($array[$key])) {
				$array[$key] = [$array[$key]];
			}

			$array = &$array[$key];
		}

		$array = $value;
	}

	public function push($key, $value = null) {
		if (is_null($value)) {
			return array_push($this->attributes, $key);
		}

		if (!$this->has($key)) {
			$this->set($key, $value);
			return $this;
		} else if (is_array($value)) {
			switch (count($value)) {
			case 1:
				$this->set("$key." . key($value), current($value));
				break;
			default:
				foreach ($value as $name => $val) {
					$this->add($name, $val);
				}
				break;
			}
			return $this;
		} else {
			$array = &$this->reference($key);
		}

		$isArray = true;
		if (!is_array($array)) {
			$isArray = false;
		}

		if (is_array($value)) {
			$array = array_merge($isArray ? $array : [$array], $value);
		} elseif ($isArray) {
			$array[] = $value;
		} else {
			$array = [$array, $value];
		}

		return $this;
	}

	public function add($key, $value = null) {
		return $this->push($key, $value);
	}

	/**
	 * 获取属性值
	 *
	 * @param string  $key        属性名
	 * @param mixed   $default    默认返回值
	 * @param bool    $reference  是否引用属性
	 *
	 * @return mixed
	 */
	public function &get($key, $default = null, $reference = false) {
		if (is_null($key)) {
			return $default;
		}

		if ($reference) {
			$array = &$this->attributes;
		} else {
			$array = $this->attributes;
		}

		foreach (explode(".", $key) as $segment) {
			if (!is_array($array) || !array_key_exists($segment, $array)) {
				return $default;
			}

			if ($reference) {
				$array = &$array[$segment];
			} else {
				$array = $array[$segment];
			}
		}

		return $array;
	}

	public function filter($filters, $callback = null) {
		if (!$callback instanceof Closure) {
			$callback = function ($var) use ($filters) {
				return !in_array($var, $filters);
			};
		}

		return new static(array_filter($this->attributes, $callback, ARRAY_FILTER_USE_KEY));
	}

	public function merge($array) {
		foreach ($array as $key => $value) {
			if (!$this->has($key)) {
				$this->set($key, $value);
			}
		}

		return $this;
	}

	public function &reference($key) {
		$result = &$this->get($key, null, true);
		return $result;
	}

	public function join($symbol, $key = null) {
		if (is_null($key)) {
			$result = join($this->attributes, $symbol);
		} else {
			$result = $this->get($key);
			$result = is_array($result) ? join($result, $symbol) : $result;
		}
		return $result;
	}

	public function joinKeys($symbol) {
		return join(array_keys($this->attributes), $symbol);
	}

	/**
	 * 查明是否包含属性
	 *
	 * @param string $key 属性名
	 *
	 * @return bool
	 */
	public function has($key) {
		return !is_null($this->get($key));
	}

	public function count($key = null) {
		return is_null($key) ? count($this->attributes) : count($this->get($key));
	}

	public function length($key = null) {
		return $this->count($key);
	}

	/**
	 * 移除属性
	 *
	 * @param string $key 属性名
	 */
	public function remove($key) {
		$array = &$this->attributes;
		$keys = explode('.', $key);

		while (count($keys) > 1) {
			$key = array_shift($keys);

			if (!isset($array[$key])) {
				return false;
			}

			$array = &$array[$key];
		}

		unset($array[array_shift($keys)]);
	}

	public function offsetSet($key, $value) {
		$this->set($key, $value);
	}

	public function offsetGet($key) {
		return $this->get($key);
	}

	public function offsetExists($key) {
		return $this->has($key);
	}

	public function offsetUnset($key) {
		$this->remove($key);
	}

	public function __isset($key) {
		return $this->has($key);
	}

	public function getIterator() {
		return new \ArrayIterator($this->attributes);
	}

	public function toArray() {
		return $this->attributes;
	}

	public function toJson() {
		return json_encode($this->attributes);
	}

	public function __toString() {
		return $this->toJson();
	}
}
