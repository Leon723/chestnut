<?php namespace Chestnut\Foundation\Http;

use ArrayAccess;
use Chestnut\Foundation\Http\Request;
use Chestnut\Support\Container as Container;
use Chestnut\Support\ControllerBuilder;
use Chestnut\Support\Parameter;

class Route implements ArrayAccess {
	/**
	 * 路由属性存储
	 *
	 * @var Chestnut\Support\Parameter
	 */
	protected $attributes;

	/**
	 * 构造路由实例
	 *
	 * @param string  $url    路由路径
	 * @param array   $option 路由配置
	 */
	public function __construct($method, $pattern, $options = []) {
		$this->attributes = new Parameter($options);
		$this->method($method);

		$this['pattern'] = $pattern;
	}

	/**
	 * 匹配路由
	 *
	 * @param Chestnut\Foundation\Http\Request $request 请求实例
	 *
	 * @return bool
	 */
	public function match($method, $uri) {
		// if(! $this->matchDomain($request->domain())) {
		//   return false;
		// }

		if (!$this->supportMethod($method)) {
			return false;
		}

		if (!$this->matchUrl($uri)) {
			return false;
		}

		if (!$this->parseUrl($uri)) {
			return false;
		}

		$this->registerMiddleware();

		return true;
	}

	public function dispatch(Container $app) {
		$controller = $this['controller'];

		$builder = new ControllerBuilder($controller);

		$builder->inject($app, $this['parameters']);

		return $builder->build();
	}

	/**
	 * 添加参数条件
	 *
	 * @param array|string  $key        参数键名
	 * @param regexString   $condition  参数匹配条件
	 */
	public function condition($key, $condition = null) {
		if (is_array($key)) {
			foreach ($key as $name => $condition) {
				$this->condition($name, $condition);
			}
			return $this;
		}

		$this->attributes->add("condition", [$key, $condition]);
		return $this;
	}

	/**
	 * 添加路由支持的请求方法
	 *
	 * @param string $method 请求方法
	 *
	 * @return void
	 */
	public function method($method) {
		if (is_array($method)) {
			foreach ($method as $name) {
				$this->method($name);
			}
			return $this;
		}

		$this->attributes->add('method', [strtoupper($method) => true]);
		return $this;
	}

	/**
	 * 查明路由是否支持请求方法
	 *
	 * @param string $method 请求方法
	 *
	 * @return bool
	 */
	public function supportMethod($method) {
		return $this->attributes->has("method." . strtoupper($method)) && $this["method." . strtoupper($method)];
	}

	public function offsetSet($key, $value) {
		$this->attributes[$key] = $value;
	}

	public function offsetGet($key) {
		return $this->attributes[$key];
	}

	public function offsetExists($key) {
		return $this->attributes->has($key);
	}

	public function offsetUnset($key) {
		$this->attributes->remove($key);
	}

	private function matchDomain($domain) {
		if (!$this['domain']) {
			return true;
		}

		if ($this['domain'] !== $domain) {
			return false;
		}

		return true;
	}
	/**
	 * 匹配路径
	 *
	 * @param Chestnut\Http\Request $request 请求实例
	 *
	 * @return bool
	 */
	private function matchUrl($uri) {
		if (!$url = strstr($this['pattern'], ':', true)) {
			$url = $this['pattern'];
		}

		$url = strlen($url) > 1 ? rtrim($url, '/') : $url;

		if ($url !== $uri && $url !== substr($uri, 0, strpos($this['pattern'], "/:"))) {
			return false;
		}

		return true;
	}

	/**
	 * 解析 URL 获取参数
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function parseUrl($url) {
		$params = $this->getParams($this['pattern']);

		$urlParamString = substr($url, strpos($this['pattern'], "/:"));
		$urlParams = $this->getParams($urlParamString, true);

		if (count($params) >= 1 && count($params) !== count($urlParams)) {
			return false;
		}

		$result = [];

		foreach ($params as $index => $key) {
			if (array_key_exists($key, $result)) {
				throw new \RuntimeException("The parameter [$key] has already exists, please check your route url");
			}

			$key = explode('@', $key);

			if ($this->attributes->has('condition.' . $key[0])) {
				$key[1] = $this["condition." . $key[0]];
			}

			if (count($key) > 1 && preg_match("/$key[1]+/", $urlParams[$index])) {
				$result[$key[0]] = $urlParams[$index];
			} else if (count($key) === 1) {
				$result[$key[0]] = $urlParams[$index];
			} else {
				return false;
			}
		}

		$this['parameters'] = $result;

		return true;
	}

	/**
	 * 获取路径上的参数
	 *
	 * @param string  $url        路径
	 * @param bool    $isRequest  是否为请求路径
	 *
	 * @return array
	 */
	private function getParams($url, $isRequest = false) {
		$segment = $isRequest ? "/" : "/:";

		$params = explode($segment, $url);

		array_shift($params);

		return $params;
	}

	public function registerMiddleware() {
		if (isset($this['middleware'])) {
			foreach ($this['middleware'] as $middleware) {
				$middleware = 'App\\Middlewares\\' . $middleware;
				(new $middleware)->register();
			}
		}
	}
}
