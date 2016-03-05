<?php
namespace Chestnut\Http;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Response extends SymfonyResponse {
	public function redirect($url, $status = 302, $headers = []) {
		$this->setStatusCode($status);
		$this->headers->set('Location', $url);

		return false;
	}
}
