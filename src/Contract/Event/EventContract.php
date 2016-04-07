<?php
namespace Chestnut\Contract\Event;

interface EventContract {
	public function listen($event, $object, $callback = null);
	public function fire($event, $params);
}