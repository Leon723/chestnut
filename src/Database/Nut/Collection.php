<?php
namespace Chestnut\Database\Nut;

use Chestnut\Support\Parameter;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Collection extends Parameter {

	public function first() {
		return reset($this->attributes);
	}

	public function compare($array) {
		$compare = [];

		if (!$array instanceof static ) {
			$array = (array) $array;
		}

		foreach ($this->keys() as $key) {
			if ($this[$key] == $array[$key]) {
				continue;
			}

			$compare[$key] = $this[$key];
		}

		return count($compare) ? new static($compare) : [];
	}

	public function toArray() {
		return array_map(function ($value) {
			if (is_object($value) && method_exists($value, 'toArray')) {
				return $value->toArray();
			}

			return $value;
		}, $this->attributes);
	}

	public function toJson() {
		return json_encode($this->toArray());
	}

	public function getHiddenIterator($hidden) {
		return $this->filter($hidden)->getIterator();
	}
}
