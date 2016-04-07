<?php
namespace Chestnut\Events;

use Chestnut\Contract\Event\EventContract;
use Chestnut\Support\Parameter;
use Chestnut\Support\Reflection\Reflector;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 * Chestnut Events Service
 */
class Event implements EventContract {
	protected $container;

	protected $listener;

	public function __construct($app) {
		$this->container = $app;

		$this->listener = new Parameter;
	}

	public function listen($event, $object, $method = null) {
		$this->addListener($event, compact('object', 'method'));
	}

	public function fire($event, $params = []) {
		$eventObj = $this->getListener($event);

		if (!$eventObj) {
			return;
		}

		if (!array_key_exists('object', $eventObj)) {
			foreach ($eventObj as $ev_name => $ev) {
				$this->fire($event . '.' . $ev_name, $params);
			}

			return;
		}

		$event_dispatch = new Reflector($eventObj['object'], $eventObj['method']);

		if ($event_dispatch->isExists()) {
			$event_dispatch->inject($params, $this->container);

			$event_dispatch->resolve();
		}
	}

	public function getListener($event) {
		return $this->listener->get($event);
	}

	public function getListeners() {
		return $this->listener;
	}

	private function addListener($event, $value) {
		$this->listener->set($event, $value);
	}
}
