<?php
namespace Chestnut\Routing;

use ArrayAccess;
use Chestnut\Http\Request;
use Chestnut\Support\Container as Container;
use Chestnut\Support\Parameter;
use Chestnut\Support\Reflection\Reflector;
use Closure;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
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

		$this->setPattern($pattern);
		$this->setIdentifier($pattern);
	}

	public function setPattern($pattern) {
		$prefix = is_array($this['prefix']) ? join($this['prefix'], '') : $this['prefix'];

		$pattern = $prefix == '/' ? $pattern : $prefix . $pattern;

		$this['pattern'] = preg_replace_callback('/{(\w*?)(\[\S*?\])?}/', function ($matches) {
			return $this->parseUrl($matches);
		}, $pattern);
	}

	public function setIdentifier($pattern) {

		$prefix = is_array($this['prefix']) ? join($this['prefix'], '') : $this['prefix'];

		$pattern = $prefix == '/' ? $pattern : $prefix . $pattern;

		$identifier = preg_replace_callback('/{(\w*?)(\[\S*?\])?}/', function ($matches) {
			return "%s";
		}, $pattern);

		$this['identifier_match'] = $identifier;

		$identifier = explode('/', ltrim($identifier, '/'));
		$end = count($identifier) - 1;

		if ($identifier[$end] === '') {
			$identifier[$end] = 'index';
		}

		$parent = count($identifier) > 2 ? [$identifier[0], $identifier[1]] : $identifier;

		$this['parent'] = join($parent, ".");
		$identifier = join($identifier, '.');

		$this['identifier'] = $identifier;
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

		$this->registerMiddleware();

		return true;
	}

	public function dispatch(Container $app) {
		$controller = $this['controller'];

		if (isset($this['namespace']) && is_array($this['namespace']) && !$controller instanceof Closure) {
			$controller = rtrim(join($this['namespace'], '\\'), "\\") . "\\" . $controller;
		} elseif (isset($this['namespace']) && !$controller instanceof Closure) {
			$controller = $this['namespace'] . '\\' . $controller;
		}

		$builder = new Reflector($controller);

		$builder->inject($this['parameters'], $app);

		return $builder->resolve();
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

		$this->attributes->add("condition", [$key => $condition]);
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

	public function matchUrl($url) {
		if ($url === $this['pattern']) {
			return true;
		}

		if (preg_match("#^{$this['pattern']}$#u", $url, $matches)) {
			foreach ($this->getParameters() as $key => $param) {
				if ($this->hasCondition($key) && !preg_match($this->getCondition($key), $matches[$key])) {
					return false;
				}

				$this->setParameter($key, $matches[$key]);
			}

			return true;
		}

		return false;
	}

	public function getParameters() {
		return $this['parameters'];
	}

	public function getIdentifier() {
		return join(explode('.%s', $this['identifier']), '');
	}

	public function getParent() {
		return $this['parent'];
	}

	public function identifierMatch() {
		$args = func_get_arg(0);

		array_unshift($args, $this['identifier_match']);

		$match = count($args) > 1 ? call_user_func_array('sprintf', $args) : array_shift($args);

		if ($this->matchUrl($match)) {
			return $match;
		}

		return false;
	}

	public function getCondition($key) {
		return $this['condition.' . $key];
	}

	public function hasCondition($key) {
		return isset($this['condition.' . $key]);
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
	 * 解析 URL
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function parseUrl($matches) {
		$name = $matches[1];
		$this->setParameter($name);

		if (isset($matches[2])) {
			$this->condition($name, "#" . $matches[2] . "+#");
		}

		return "(?P<$name>[\pL0-9~%.:_-]+)";
	}

	private function setParameter($name, $value = null) {
		$this->attributes->set("parameters.$name", $value);
	}

	public function registerMiddleware() {
		if (isset($this['middleware'])) {
			foreach ($this['middleware'] as $middleware) {
				if (empty($middleware)) {
					continue;
				}

				$middleware = 'App\\Middlewares\\' . $middleware;
				(new $middleware)->register();
			}
		}
	}
}
