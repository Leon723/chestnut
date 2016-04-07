<?php
namespace Chestnut\Contract\Database;

interface ConnectionContract {
	public function connect($config);
}