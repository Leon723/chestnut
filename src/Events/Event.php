<?php
namespace Chestnut\Events;

use Chestnut\Contract\Support\Container as ContainerContract;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 * Chestnut Events Service
 */
class Event {
	protected $container;

	protected $listener = [];

	public function __construct(ContainerContract $app) {
		$this->container = $app;
	}
}