<?php
namespace Chestnut\Support;

use Chestnut\Contract\Support\Container as ContainerContract;
use Chestnut\Contract\Support\ControllerBuilder as ControllerBuilderContract;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class ControllerBuilder implements ControllerBuilderContract {
	/**
	 * Class Name
	 * @var string|Closure
	 */
	protected $class;
	protected $type;
	protected $dependencies = [];
	protected $reflector;
	protected $injected = false;

	/**
	 * ControllerBuilder Constructor
	 * @param string|Closure $class ControllerClass
	 */
	public function __construct($class) {
		$this->analysis($class);

		$this->reflector = $this->getReflector();
	}

	/**
	 * Analysis the class to get reflector
	 * @param  string|Closure $class ControllerClass
	 * @return void
	 */
	public function analysis($class) {
		if ($class instanceof Closure) {
			$this->type = 'closure';
			$this->class = $class;

			return;
		}

		if (strpos($class, '::') !== false) {
			$this->type = 'method';

			$class = explode('::', $class);

			$this->class = $class;

			return;
		}

		$this->class = $class;
		$this->type = 'class';
	}

	/**
	 * get Reflector
	 * @return Reflector
	 */
	public function getReflector() {
		switch ($this->type) {
		case 'closure':
			return $this->reflectionClosure();
			break;
		case 'method':
			return $this->reflectionMethod();
			break;
		case 'class':
			return $this->reflectionClass();
			break;
		}
	}

	/**
	 * Set dependencies
	 * @param array $dependencies Controller dependencies
	 */
	public function setDependencies($dependencies) {
		if (!$this->injected() || !$this->isNeedInject()) {
			$this->dependencies = $dependencies;
		} else {
			throw new \RuntimeException("Can't reset dependencies when the builder had been injected.");
		}
	}

	/**
	 * Get dependencies
	 * @return array
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	public function inject(ContainerContract $c, $parameters = []) {
		$inject = [];
		$dependencies = $this->getDependencies();
		$missing = [];

		foreach ($dependencies as $dependency) {
			if (is_array($parameters) && array_key_exists($name = $dependency->name, $parameters)) {
				$inject[$name] = $parameters[$name];
			} elseif ($c->registered($name = $dependency->name)) {
				$inject[$name] = $c->make($name);
			} elseif ($dependency->getClass() && $c->registered($name = $dependency->getClass()->name)) {
				$inject[$name] = $c->make($name);
			} elseif ($dependency->isDefaultValueAvailable()) {
				$inject[$dependency->name] = $dependency->getDefaultValue();
			} else {
				$missing[] = $dependency->getClass() ? $dependency->getClass()->name : $dependency->name;
			}
		}

		if (!empty($missing)) {
			$buildStack = $c->getBuildStack();

			throw new \InvalidArgumentException('Missing ' . count($missing) . ' parameter [ ' . join($missing, ', ') . ' ] in ' . end($buildStack));
		}

		$this->injected = true;
		$this->dependencies = $inject;
	}

	public function injected() {
		if (!$this->isNeedInject()) {
			return true;
		}

		return $this->injected;
	}

	public function isNeedInject() {
		return count($this->dependencies);
	}

	/**
	 * Instantiated class
	 *
	 * @param  array  $dependencies class dependencies
	 * @return mixed
	 */
	public function build() {
		if (!$this->injected()) {
			throw new \RuntimeException('This builder has not inject dependencies');
		}

		if (is_callable($this->reflector)) {
			return call_user_func_array($this->reflector, $this->dependencies);
		}

		return $this->reflector->newInstanceArgs($this->dependencies);
	}

	/**
	 * resolve Closure Reflector
	 * @return Reflector
	 */
	public function reflectionClosure() {
		$reflector = new ReflectionFunction($this->class);

		$this->setDependencies($reflector->getParameters());

		return $this->class;
	}

	/**
	 * resolve Method Reflector
	 * @return Reflector
	 */
	public function reflectionMethod() {
		$reflector = new ReflectionMethod($this->class[0], $this->class[1]);

		$this->setDependencies($reflector->getParameters());

		$reflector = $reflector->getClosure(new $this->class[0]);

		return $reflector;
	}

	/**
	 * resolve Class Reflector
	 * @return Reflector
	 */
	public function reflectionClass() {
		$reflector = new ReflectionClass($this->class);

		if (is_null($reflector->getConstructor())) {
			$this->setDependencies([]);
		} else {
			$this->setDependencies($reflector->getConstructor()->getParameters());
		}

		return $reflector;
	}
}
