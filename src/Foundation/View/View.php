<?php namespace Chestnut\Foundation\View;

use Chestnut\Support\Cache;
use Chestnut\Support\File;

class View {
	protected $properties;
	protected $fileName;

	public function __construct() {
	}

	public function setFile($filename) {
		$filename = explode('.', $filename);
		$this->fileName = join($filename, '/');

		return $this;
	}

	public function data(array $data) {
		if (is_null($this->properties)) {
			$this->properties = $data;
		} else {
			foreach ($data as $key => $item) {
				$this->properties[$key] = $item;
			}
		}

		return $this;
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
		extract($this->properties);

		if (!$this->checkCache()) {
			$this->template();
		}

		require $this->getCachePath();
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
		try {
			ob_start();
			$this->display();
			return ob_get_clean();
		} catch (\RuntimeException $e) {
			throw $e;
		}
	}

}
