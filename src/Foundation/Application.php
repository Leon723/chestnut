<?php
namespace Chestnut\Foundation;

use Chestnut\Auth\AuthStatic as Auth;
use Chestnut\Contract\Support\Container as ContainerContract;
use Chestnut\Events\EventRegister;
use Chestnut\Http\HttpRegister;
use Chestnut\Http\Response;
use Chestnut\Support\Container;
use Chestnut\Support\File;
use Chestnut\Support\Parameter;
use Chestnut\Support\Reflection\Reflector;
use Chestnut\View\View as ViewContract;
use View;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Application extends Container implements ContainerContract {
	protected $middleware = [];
	protected $registerdService = [];
	protected $registerAliases = [];
	protected $bootstrap = [
		'Boot\initEvent',
	];

	public function __construct($basePath = null) {
		parent::__construct();

		static::setInstance($this);
		$this->instance('app', $this);

		$this->initConfig($basePath);
		$this->registerBaseComponent();

		$this->bootstrap();
	}

	public function registerBaseComponent() {
		(new EventRegister($this))->register();
		(new HttpRegister($this))->register();
	}

	public function resolveRegister($register) {
		if (isset($this->registerdService[$register])) {
			return;
		}

		if (is_string($register)) {
			$this->registerdService[$register] = true;
			return new $register($this);
		}
	}

	public function registerAlias($alias, $class) {
		$this->registerAliases[$alias] = $class;
	}

	public function initConfig($basePath) {
		$this->instance('config', new Parameter);

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

		if ($this->config->has('app.timezone')) {
			date_default_timezone_set($this->config->get('app.timezone', 'Asia/Chongqing'));
		}
	}

	public function bootstrap() {
		foreach ($this->bootstrap as $boot) {
			$boot = __NAMESPACE__ . "\\" . $boot;
			(new $boot($this))->boot();
		}

		$this->event->fire('init');
	}

	public function basePath($path = '') {
		return $this['path.base'] . $path;
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

	public function isDispatchable() {
		return $this->resolved('current');
	}

	public function registerMiddleware($middleware) {
		array_unshift($this->middleware, $middleware);
	}

	public function resolveAlias() {
		foreach ($this->registerAliases as $alias => $className) {
			class_alias($className, $alias, true);
		}
	}

	public function run() {
		foreach ($this->bootstrap as $boot) {
			$boot = __NAMESPACE__ . "\\" . $boot;
			(new $boot($this))->boot();
		}

		$this->instance('current', $this->route->match(
			$this->request->method(),
			$this->request->path()
		));

		if ($this->config->get('app.debug', false)) {
			$whoops = new \Whoops\Run;
			$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
			$whoops->register();
		}

		$this->event->fire('boot.boot');
	}

	public function registerServices() {
		$registers = $this->config->get('app.registers');

		foreach ($registers as $register) {
			$register = $this->resolveRegister($register);
			$register->register();
		}
	}

	public function boot() {
		$this->callMiddleware();

		$this->dispatch();

		$xsrf_token = time() . $this->auth->getAccount();

		if (!$this->request->isAjax()) {
			session('xsrf_token', $xsrf_token);
			cookie_remove('chestnut_xsrf_token');
			cookie('chestnut_xsrf_token', $xsrf_token);
		}

		$this->response->send();
	}

	public function dispatch() {
		if ($this->response->isRedirection() || $this->response->isForbidden()) {
			return;
		}

		if (!$this->isDispatchable()) {
			return $this->notFound();
		}

		View::addGlobal('__current_parent', $this->current->getParent());
		View::addGlobal('__current', $this->current->getIdentifier());

		try {
			list($controller, $parameters) = $this->current->dispatch();

			$reflector = new Reflector($controller);
			$reflector->inject($parameters, $this);

			ob_start();
			$object = $reflector->resolve();
			$result = ob_get_clean();

			if (!$object && !$result) {
				return;
			}

			if ($object instanceof ViewContract) {
				$result = $object->render();
			}

			if (strlen($result) === 0) {
				$this->response->setContent($object);
			} else {
				$this->response->setContent($result);
			}

			$this->response->prepare($this->request);

			return;
		} catch (\Exception $e) {
			if ($this->config->get('app.debug', false)) {
				throw $e;
			}
		}

	}

	public function permissionDenined() {
		$this->response->setStatusCode(Response::HTTP_FORBIDDEN);
		$this->response->setContent($this->view->make('error.403')->render());
	}

	public function notFound() {
		$this->response->setStatusCode(Response::HTTP_NOT_FOUND);
		$this->response->setContent($this->view->make('error.404')->render());
	}

	public function callMiddleware() {
		while ($middleware = array_shift($this->middleware)) {
			$result = $middleware->call($this->request);

			if ($result === Auth::PERMISSION_DENIED) {
				$this->permissionDenined();
				$result = false;
			}
		}
	}
}
