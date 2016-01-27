<?php namespace Chestnut\Foundation\View;

class TemplateEngine {
	protected $path;
	protected $rootDir;
	protected $section;

	public function __construct($path) {
		$this->path = $path;
		$this->rootDir = app()->path() . config('view.templates', 'views' . DIRECTORY_SEPARATOR);
	}

	public function make() {
		return $this->parse();
	}

	private function getView() {
		$view = file_get_contents($this->path);

		if (preg_match_all("#{@ *section\((.*?)\) *}([\w\W]*?){@ *endsection *}#", $view, $m)) {
			foreach ($m[1] as $key => $section) {
				$this->setSection($section, $m[2][$key]);
			}
		} else {
			$this->setSection(0, $view);
		}

		return $this->getLayout($view);
	}

	private function getParameter($key) {
		return $this->parameters[$key];
	}

	private function setSection($section, $value) {
		if ($this->section === null) {
			$this->section = [];
		}

		$this->section[$section] = $value;
	}

	private function getSection($section) {
		return isset($this->section[$section]) ? $this->section[$section] : '';
	}

	private function getLayout($view) {
		preg_match("#{@ *layout\((.*?)\) *}#", $view, $m);

		$layoutPath = count($m) ? join("/", explode(".", $m[1])) . ".php" : false;

		if ($layoutPath && file_exists($this->rootDir . $layoutPath)) {
			return file_get_contents($this->rootDir . $layoutPath);
		}

		return false;
	}

	private function parse() {
		$regs = [
			"parseNote" => '#{{-- *.* *--}}#',
			"parseFor" => '#{@for:(\S*)\s*in\s*(\S*)}#',
			"parseIf" => '#{@(if(?=\:)|elseif(?=\:)|else)(?:\:?(.*))}#',
			"parseInclude" => '#{@ *include\((.*?)\) *}#',
			"parseVar" => '#{{ *([\S\s]*?)(?:(?: *\|([\s\S]*?))?|(?: *(or|\?\:) *(\w+))?)? *}}#',
			"parseEnd" => '#{@ *end(?:\S*) *}#',
		];

		if ($layout = $this->getView()) {
			$content = preg_replace_callback("#{@ *section\((.*?)\) *}#", function ($m) {
				return $this->getSection($m[1]);
			}, $layout);
		} else {
			$content = $this->getSection(0);
		}

		foreach ($regs as $type => $reg) {
			$content = preg_replace_callback($reg, function ($m) use ($type) {
				return $this->$type($m);
			}, $content);
		}

		return $content;
	}

	private function parseNote($m) {
		return "";
	}

	private function parseVar($m) {
		if (preg_match('/(\S*?)(?:\(([\S\s]*)\))$/', $m[1], $matches)) {
			$echo = count($matches) > 2 ? "{$matches[1]}({$matches[2]})" : "$matches[0]";
		} elseif (preg_match('/([\S\s]+)\?([\S\s]+)\:([\S\s]+)/', $m[1])) {
			$echo = $m[1];
		} elseif (preg_match('/(\S*?)([.:]{1}?)(\S*)/', $m[1], $matches)) {
			$operator = $matches[2] == '.' ? ['->', ''] : ['[\'', '\']'];
			$echo = "\${$matches[1]}{$operator[0]}{$matches[3]}{$operator[1]}";
		} else {
			$echo = "\${$m[1]}";
		}

		if (count($m) == 3 && !empty($m[2])) {
			$filters = explode(' ', trim($m[2]));

			$echo = $this->processFilter($filters, $echo);
		}

		if (count($m) > 3) {
			$echo = $this->processOperation($echo, array_slice($m, 3));
		}

		return "<?php echo $echo; ?>";
	}

	private function processFilter($filters, $object) {
		$filter = new Filter($filters);

		return $filter->parse($object);
	}

	private function processOperation($object, $operation) {
		switch ($operation[0]) {
		case 'or':
		case '?:':
			return "isset({$object}) ? {$object} : {$operation[1]}";
		default:
			return "{$object} {$operation[0]} {$operation[1]}";
		}
	}

	private function parseFor($m) {
		$item = explode(",", $m[1]);
		$parent = $m[2];

		return "<?php foreach(\$$parent as \$" . join("=> $", $item) . ") { ?>";
	}

	private function parseInclude($m) {
		if (preg_match_all('/[$\w.]+/', $m[1], $matches)) {
			$path = 'views' . DIRECTORY_SEPARATOR . join(explode('.', $matches[0][0]), DIRECTORY_SEPARATOR) . '.php';
			if (isset($matches[0][1])) {
				$data = $matches[0][1];
			} else {
				$data = '[]';
			}

			if (file_exists(app_path($path))) {
				return "<?php echo view('{$matches[0][0]}', {$data}); ?>";
			}
		}

	}

	private function parseIf($m) {
		switch ($m[1]) {
		case "if":
			return "<?php $m[1]($m[2]) { ?>";
			break;
		case "elseif":
			return "<?php } $m[1]($m[2]) { ?>";
			break;
		case "else":
			return "<?php } $m[1] { ?>";
			break;
		}
	}

	private function parseEnd($m) {
		return "<?php } ?>";
	}

}
