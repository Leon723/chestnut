<?php
namespace Chestnut\View\Engine;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
abstract class Engine {
	public function __construct() {
	}

	abstract function render($content);
}