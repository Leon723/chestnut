<?php
namespace Chestnut\Database;

use Chestnut\Support\Parameter;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Collection extends Parameter {
	protected $paginate;

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

	public function paginate() {
		return $this->paginate;
	}

	public function setPaginate($paginate) {
		$this->paginate = $paginate;
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