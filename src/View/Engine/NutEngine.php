<?php
namespace Chestnut\View\Engine;

use Chestnut\View\Filter\Filter;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class NutEngine extends Engine {
	protected $contentTag = ['{{', '}}'];
	protected $methodTag = ['{@', '}'];
	protected $commitTag = ['{{--', '--}}'];

	protected $parseQueue = [
		"parseCommit",
		"parseMethod",
		"parseEcho",
	];

	protected $hasLayout = false;

	public function render($content) {
		return $this->parse($content);
	}

	private function parse($content) {
		foreach ($this->parseQueue as $parser) {
			$content = $this->$parser($content);
		}

		if ($this->hasLayout) {
			$content .= "\n\n<?php \$this->renderLayout(\$this->data); ?>";
		}

		return $content;
	}

	private function parseCommit($content) {
		return preg_replace("/{$this->commitTag[0]}\s*.*?\s*{$this->commitTag[1]}/s", '<?php /*$1*/?>', $content);
	}

	private function parseMethod($content) {
		$callback = function ($m) {
			return $this->{'parse' . ucfirst($m[1])}(array_slice($m, 2));
		};

		return preg_replace_callback("/{$this->methodTag[0]}(.+?)(?:\:(.+?))?{$this->methodTag[1]}/s", $callback, $content);
	}

	private function parseEcho($content) {
		$callback = function ($m) {
			return $this->parseContent($m);
		};

		return preg_replace_callback("/{$this->contentTag[0]}\s*(.+?)\s*((\?\:|\s+or\s+|\?|\|)+\s*(.+?))?\s*{$this->contentTag[1]}/s", $callback, $content);
	}

	private function parseLayout($value) {
		$this->hasLayout = true;

		return "<?php \$this->layout('{$value[0]}'); ?>";
	}

	private function parseSection($value) {
		$this->currentSection = $value[0];

		return "<?php \$this->sectionStart('{$value[0]}'); ?>";
	}

	private function parseEndsection($value) {
		return "<?php \$this->sectionEnd(); ?>";
	}

	private function parseShow() {
		return "<?php \$this->showSection(); ?>";
	}

	private function parseContent($m, $force = false) {
		if (empty($m[1])) {
			return $m[1];
		}

		if (preg_match('/^(\w+?)(?:\((.*)\))/', $m[1], $matches)) {
			$echo = count($matches) > 2 ? "{$matches[1]}({$matches[2]})" : "$matches[0]";
		} elseif (preg_match('/^[\'\"].*[\'\"]$/', $m[1])) {
			$echo = "{$m[1]}";
		} elseif (preg_match("/(.+?) * ([=]+) *(.+?)/", $m[1], $match)) {
			$match[1] = $this->parseContent(['', $match[1]], true);
			$match[3] = $this->parseContent(['', $match[3]], true);

			$echo = $match[1] . " {$match[2]} " . $match[3];
		} elseif (preg_match_all('/([.:]?\$?)([\w]+)(\((.+)?\))?/s', $m[1], $match)) {

			$echo = "";
			$operators = ['.' => ['->', ''], ":" => ['[\'', '\']'], ':$' => ['[$', ']'], '$' => ['$', '']];

			for ($i = 0; $i < count($match[0]); $i++) {
				if (!empty($match[1][$i])) {
					$operator = $operators[$match[1][$i]];

					$echo .= "{$operator[0]}{$match[2][$i]}{$operator[1]}";
				} elseif ((int) $match[2][$i] > 0 && (int) $match[2][$i] == $match[2][$i]) {
					$echo .= $match[2][$i];
				} elseif ($i == 0) {
					$echo .= '$' . $match[2][$i];
				} else {
					$echo .= '->' . $match[2][$i];
				}

				if (!empty($match[3][$i])) {
					$value = $this->parseContent(['', $match[4][$i]], true);
					$echo .= '(' . $value . ')';
				}
			}
		} else {
			$echo = start_with($m[1], "$") ? "{$m[1]}" : "\${$m[1]}";
		}

		if (isset($m[3]) && $m[3] == '|') {
			$filters = explode(' ', trim($m[4]));

			$echo = $this->processFilter($filters, $echo);
		}

		if (count($m) > 3) {
			$echo = $this->processOperation($echo, array_slice($m, 3));
		}

		if (!$force) {
			return "<?php echo $echo; ?>";
		} else {
			return $echo;
		}
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
		case '?':
			list($true, $false) = explode(':', $operation[1]);

			$true = $this->parseContent(['', trim($true)], true);
			$false = $this->parseContent(['', trim($false)], true);

			return "{$object} ? {$true} : {$false}";
		default:
			return $object;
		}
	}

	private function parseFor($m) {
		preg_match_all('/[\w\$\:\.\[\]\-\>]+/', $m[0], $match);

		$parent = $this->parseContent(['', array_pop($match[0])], true);
		$key = $this->parseContent(['', array_shift($match[0])], true);
		$value = current($match[0]) == 'in' ? '' : '=> ' . $this->parseContent(['', array_shift($match[0])], true);

		return '<?php if(' . $parent . ' && isset(' . $parent . ')) foreach(' . $parent . ' as ' . $key . $value . ') { ?>';
	}

	private function parseEndfor($value) {
		return "<?php } ?>";
	}

	private function parseInclude($value) {
		return "<?php echo \$this->factory->make('{$value[0]}')->render(); ?>";
	}

	private function parseIf($value) {
		return "<?php if({$value[0]}) { ?>";
	}

	private function parseElse() {
		return "<?php } else { ?>";
	}

	private function parseEndif() {
		return '<?php } ?>';
	}

	private function parseEnd($m) {
		return "<?php } ?>";
	}

}
