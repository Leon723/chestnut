<?php
namespace Chestnut\Database;

class Raw {
	protected $string;
	public function __construct($string) {
		$this->string = (string) $string;
	}

	public function __toString() {
		return $this->string;
	}
}