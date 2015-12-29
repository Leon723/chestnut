<?php
namespace Chestnut\Foundation\Database;

use Chestnut\Support\Parameter;

class Collection extends Parameter {
	public function compare($array) {
		if (!$array instanceof static ) {
			$array = (array) $array;
		}

		foreach ($this->keys() as $key) {
			if ($this[$key] == $array[$key]) {
				continue;
			}

			return false;
		}

		return true;
	}
}