<?php namespace Chestnut\Foundation\Http;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

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

	public function all() {
		$request = $this->request->all();
		$query = $this->query->all();

		return array_merge($request, $query);
	}
}
