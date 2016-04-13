<?php
namespace Chestnut\View\Filter;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Filter {
	protected $filters;

	public function __construct($filters) {
		$this->filters = $filters;
	}

	public function getFilters() {
		return $this->filters;
	}

	public function compile($object) {
		foreach ($this->getFilters() as $filter) {
			if (strpos($filter, ":")) {
				$explode = explode(":", $filter);

				$filter = $explode[0];
				$params = empty($explode[1]) ? $explode[0] : $explode[1];
				$object = $this->$filter($object, $params);
			} else {
				$object = $this->$filter($object);
			}

		}

		return $object;
	}

	private function escape($object, $type = 'e') {
		switch ($type) {
		default:
			return "htmlspecialchars({$object}, ENT_QUOTES, 'UTF-8', false)";
		}
	}

	private function number_format($object, $type) {
		return "number_format({$object}, {$type})";
	}

	private function e($object, $type = 'e') {
		return $this->escape($object, $type);
	}

	private function join($object, $params) {
		return "join({$object}, {$params})";
	}
}
