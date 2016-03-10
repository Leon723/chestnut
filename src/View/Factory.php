<?php
namespace Chestnut\View;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Factory {
	/**
	 * Application Container
	 * @var \Chestnut\Foundation\Application
	 */
	protected $app;

	/**
	 * View Factories
	 * @var \Chestnut\View\Factory;
	 */
	protected $views;

	/**
	 * Global Scope
	 * @var array
	 */
	protected $globalScope = [];

	/**
	 * View Render Stack
	 * @var array
	 */
	protected $renderStack = [];

	/**
	 * Last Render View
	 * @var string
	 */
	protected $lastRender;

	public function __construct($app) {
		$this->app = $app;
	}

	public function getViews() {
		return $this->views;
	}

	public function make($filename, $data = []) {
		$view = new View($this);

		$view->setPath(
			$this->app->basePath($this->app->config->get('app.view.templates', 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR))
		);

		$view->setCachePath(
			$this->app->cachePath($this->app->config->get('app.view.cache', 'views' . DIRECTORY_SEPARATOR))
		);

		$view->setFilename($filename);

		$view->data($data);

		return $view;
	}

	public function getGlobal() {
		return $this->globalScope;
	}

	public function hasGlobal() {
		return !empty($this->globalScope);
	}

	public function resolveEngine($engine) {
		return $this->app->make($engine);
	}

	public function addGlobal($key, $value) {
		if (is_array($key)) {
			foreach ($key as $name => $item) {
				$this->addGlobal($name, $item);
			}
		}

		if (func_num_args() > 2) {
			$keys = func_get_args();
			$value = array_pop($keys);

			$data = &$this->globalScope[array_shift($keys)];

			while ($key = array_shift($keys)) {
				if (!isset($data[$key])) {
					$data[$key] = [];
				}

				$data = &$data[$key];
			}

			return $data = $value;
		}

		$this->globalScope[$key] = $value;
	}

	public function __call($key, $params) {
		$view = $this->getRendering();

		if (method_exists($view, $key)) {
			call_user_func_array([$view, $key], $params);
		}

		if ($this->app->config->has('app.caseSensitive') && $this->config->get('app.caseSensitive')) {
			$view->data([$key => $params[0]]);
		} else {
			$view->data([strtolower($key) => $params[0]]);
		}

		return $this;
	}
}
