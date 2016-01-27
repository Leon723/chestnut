<?php
namespace Chestnut\Foundation\Database;

use Chestnut\Support\Parameter;

class Collection extends Parameter {
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

	public function getHiddenIterator($hidden) {
		return $this->filter($hidden)->getIterator();
	}
}