<?php
namespace Chestnut\Http;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Request extends SymfonyRequest {
	public function method() {
		return $this->getMethod();
	}

	public function path() {
		return $this->getPathinfo();
	}

	public function files() {
		return $this->files;
	}

	public function isAjax() {
		return $this->isXmlHttpRequest();
	}

	public function all() {
		$this->request->remove('_method');

		$request = $this->request->all();
		$query = $this->query->all();

		return array_merge($request, $query);
	}
}
