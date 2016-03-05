<?php
namespace Chestnut\Support;

use Chestnut\Contract\Support\Container as ContainerInterface;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 *
 * Abstract Register Service Class
 */
abstract class Register {
	protected $app;

	/**
	 * @param ContainerInterface $app
	 */
	public function __construct(ContainerInterface $app) {
		$this->app = $app;
	}

	abstract function register();
}