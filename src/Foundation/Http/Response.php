<?php namespace Chestnut\Foundation\Http;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response extends SymfonyResponse {
	public function redirect($url, $status = 302, $headers = []) {
		$this->setStatusCode($status);
		$this->headers->set('Location', $url);
	}
}
