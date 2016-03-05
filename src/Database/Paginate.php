<?php
namespace Chestnut\Database;

/**
 * @author Liyang Zhang <33543015@qq.com>
 */
class Paginate {
	protected $count;
	protected $page;
	protected $perpage;
	protected $colume;
	protected $render;
	protected $boxTemp = '<div class="paginate-box">%s</div>';
	protected $itemTemp = '<a class="btn sm" href="%s">%s</a>';
	protected $currentTemp = '<a class="btn sm active">%s</a>';
	protected $disabledTemp = '<a class="btn sm disabled">...</a>';
	protected $prevNextTemp = '<a class="btn sm%s" %s>%s</a>';

	public function __construct($count, $perpage = 10, $page = 0) {
		$this->count = $count;
		$this->page = (int) $page;
		$this->perpage = $perpage;

		$this->initPaginate();
	}

	public function initPaginate() {
		$totalPage = (int) ceil($this->count / $this->perpage);
		$request = clone request();
		$range = $this->calcPageRange($totalPage);

		$item = [];

		for ($i = $range[0]; $i <= $range[1]; $i++) {
			$request->query->set('page', $i);
			$path = $request->path() . '?' . $this->convertQueryString($request->query->all());

			if ($i === $this->page) {
				array_push($item, sprintf($this->currentTemp, $i));
			} else {
				array_push($item, sprintf($this->itemTemp, $path, $i));
			}
		}

		if ($range[0] > 1) {
			$request->query->set('page', 1);
			$path = $request->path() . '?' . $this->convertQueryString($request->query->all());

			array_unshift($item, $this->disabledTemp);
			array_unshift($item, sprintf($this->itemTemp, $path, 1));
		}

		if ($range[1] < $totalPage) {
			$request->query->set('page', $totalPage);
			$path = $request->path() . '?' . $this->convertQueryString($request->query->all());

			array_push($item, $this->disabledTemp);
			array_push($item, sprintf($this->itemTemp, $path, $totalPage));
		}

		if ($this->page === 1) {
			array_unshift($item, sprintf($this->prevNextTemp, ' disabled', '', config('app.paginate.prev', '上一页')));
		} else {
			$request->query->set('page', $this->page - 1);
			$path = $request->path() . '?' . $this->convertQueryString($request->query->all());

			array_unshift($item, sprintf($this->prevNextTemp, '', "href=\"{$path}\"", config('app.paginate.prev', '上一页')));
		}

		if ($this->page === $totalPage) {
			array_push($item, sprintf($this->prevNextTemp, ' disabled', '', config('app.paginate.prev', '下一页')));
		} else {
			$request->query->set('page', $this->page + 1);
			$path = $request->path() . '?' . $this->convertQueryString($request->query->all());

			array_push($item, sprintf($this->prevNextTemp, '', "href=\"{$path}\"", config('app.paginate.prev', '下一页')));
		}

		$this->render = sprintf($this->boxTemp, join($item, ''));
	}

	public function convertQueryString($query) {
		$parts = array();

		foreach ($query as $key => $value) {
			$parts[] = $key . '=' . $value;
		}

		return implode('&', $parts);
	}

	public function render() {
		return $this->render;
	}

	public function calcPageRange($total) {
		if ($this->page === 1) {
			return [1, $total > 10 ? 10 : $total];
		}

		if ($this->page === $total) {
			return [$total > 10 ? $this->page - 10 : 1, $total];
		}

		$over = $total - $this->page >= 5 ? 5 : $total - $this->page;
		$under = 10 - $over;

		$start = $this->page - $under <= 1 ? 1 : $this->page - $under;
		$end = $this->page + $over < 10 ? 10 : $this->page + $over;

		if ($end > $total) {
			$end = $total;
		}

		return [$start, $end];
	}
}