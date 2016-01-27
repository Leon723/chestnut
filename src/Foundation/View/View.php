<?php namespace Chestnut\Foundation\View;

use Chestnut\Support\Cache;
use Chestnut\Support\File;

class View {
	protected $fileName;
	protected $properties = [];

	protected static $globalData = [];

	public function __construct() {
	}

	public function setFile($filename) {
		$filename = explode('.', $filename);
		$this->fileName = join($filename, '/');

		return $this;
	}

	public function data($data) {
		if (is_null($this->properties)) {
			$this->properties = $data;
		} else {
			foreach ($data as $key => $item) {
				$this->properties[$key] = $item;
			}
		}

		return $this;
	}

	public function getData() {
		return $this->properties;
	}

	public function make($filename, $data = []) {
		$this->setFile($filename);
		$this->data($data);

		return $this;
	}

	public function getCachePath() {
		return app()->cachePath() . config('view.cache', 'views' . DIRECTORY_SEPARATOR) . md5($this->fileName);
	}

	public function getTemplatesPath() {
		return app()->path() . config('view.templates', 'views' . DIRECTORY_SEPARATOR) . $this->fileName . ".php";
	}

	public function checkCache() {
		if (!file_exists($this->getCachePath())) {
			return false;
		}

		if (File::file_diff_time($this->getCachePath(), $this->getTemplatesPath()) < 0) {
			return false;
		}

		return false;
	}

	public function template() {
		$engine = new TemplateEngine($this->getTemplatesPath());

		$content = $engine->make();

		if (!Cache::write('views', md5($this->fileName), $content)) {
			throw new \RuntimeException("编译模板文件出错");
		}
	}

	public function display() {
		$obLevel = ob_get_level();

		ob_start();

		$properties = array_merge(static::$globalData, $this->properties);

		extract($properties);

		if (!$this->checkCache()) {
			$this->template();
		}

		try {
			require $this->getCachePath();
		} catch (\Exception $e) {
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}

			throw $e;
		}

		return ltrim(ob_get_clean());
	}

	public static function addGlobal($key, $value = null) {
		if (is_array($key)) {
			foreach ($key as $name => $item) {
				static::addGlobal($name, $item);
			}
		}

		static::$globalData[$key] = $value;
	}

	public function __call($key, $params) {
		if (config()->has('caseSensitive') && config('caseSensitive')) {
			$this->properties[$key] = $params[0];
		} else {
			$this->properties[strtolower($key)] = $params[0];
		}

		return $this;
	}

	public function __toString() {
		return $this->display();
	}

}
