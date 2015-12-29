<?php namespace Chestnut\Foundation;

use Chestnut\Contract\Support\Container as ContainerContract;
use Chestnut\Foundation\Http\Request;
use Chestnut\Foundation\Http\Response;
use Chestnut\Support\Container;
use Chestnut\Support\File;
use Chestnut\Support\Parameter;

class Application extends Container implements ContainerContract {
	protected $middleware = [];

	public function __construct($basePath = null) {
		parent::__construct();

		static::setInstance($this);

		$this->registerBaseComponent();
		$this->initConfig($basePath);
		$this->registerAlias();

		$this->registerMiddleware($this);
	}

	public function registerBaseComponent() {

		$this->singleton(['Chestnut\Foundation\Http\Request' => 'request'], function () {
			return Request::createFromGlobals();
		});
		$this->singleton(['Chestnut\Foundation\Http\Router' => 'route']);
		$this->singleton(['Chestnut\Foundation\Http\Response' => 'response']);
		$this->singleton(['Chestnut\Foundation\Http\Session' => 'session']);

		$this->register(['Symfony\Component\HttpFoundation\Cookie' => 'cookie']);

		$this->register(['Chestnut\Foundation\Dispatcher' => 'dispatch']);
		$this->register(['Chestnut\Foundation\View\View' => 'view']);

		$this->instance('config', new Parameter);
		$this->instance('app', $this);
	}

	public function initConfig($basePath) {
		if (is_null($basePath)) {
			$this->config->replace($this->getDefaultConfig());
		} else {
			$this['path.base'] = $basePath . DIRECTORY_SEPARATOR;

			foreach (['public', 'config', 'cache', 'private'] as $path) {
				$this['path.' . $path] = $this['path.base'] . $path . DIRECTORY_SEPARATOR;
			}

			$configs = File::readDir($this->configPath());

			if ($configs) {
				foreach ($configs as $configPath) {
					$this->config->set($configPath['fileName'], require $this->configPath($configPath['path']));
				}
			}
		}
	}

	public function path($path = '') {
		return $this['path.base'] . 'app' . DIRECTORY_SEPARATOR . $path;
	}

	public function publicPath($path = '') {
		return $this['path.public'] . $path;
	}

	public function configPath($path = '') {
		return $this['path.config'] . $path;
	}

	public function cachePath($path = '') {
		return $this['path.cache'] . $path;
	}

	public function privatePath($path = '') {
		return $this['path.private'] . $path;
	}

	public function getDefaultConfig() {
		return [
			'timezone' => 'Asia/Chongqing',
			'debug' => true,
		];
	}

	public function isDispatchable() {
		return $this->resolved('current');
	}

	public function registerMiddleware($middleware) {
		array_unshift($this->middleware, $middleware);
	}

	public function registerAlias() {
		foreach (config('app.alias', [
			'Route' => 'Chestnut\Foundation\Http\Router',
			'Model' => 'Chestnut\Foundation\Database\Model',
			'Schema' => 'Chestnut\Foundation\Database\Schema',
			'Session' => 'Chestnut\Foundation\Http\Session',
			'Middleware' => 'Chestnut\Component\Middleware',
		]) as $alias => $className) {
			class_alias($className, $alias, true);
		}
	}

	public function boot() {
		if ($this->config->has('app.timezone')) {
			date_default_timezone_set($this->config->get('app.timezone'));
		}

		$this->session->start();

		$this->instance('current', $this->route->match(
			$this->request->method(),
			$this->request->path()
		));

		if (!$this->callMiddleware()) {
			$middleware = end($this->middleware);
			$middleware->call();
		}
	}

	public function call() {
		$this->dispatch();

		$this->response->send();
	}

	public function dispatch() {
		if ($this->response->isRedirection()) {
			return;
		}

		if (!$this->isDispatchable()) {
			$this->response->setStatusCode(Response::HTTP_NOT_FOUND);
			$this->response->setContent(view('error.404'));

			return;
		}

		try {
			ob_start();
			$object = $this->current->dispatch($this);
			$result = ob_get_clean();

			if (strlen($result) === 0) {
				$this->response->setContent($object);
			} else {
				$this->response->setContent($result);
			}

			$this->response->prepare($this->request);

			return;
		} catch (\Exception $e) {
			if ($this->config->get('debug', true)) {
				throw $e;
			}
		}

	}

	public function callMiddleware() {
		while (count($this->middleware) > 0) {
			$middleware = array_shift($this->middleware);

			if (!$middleware->call($this->request)) {
				return false;
			}
		}
	}
}