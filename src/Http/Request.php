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

	/**
	 * Get the URL (no query string) for the request.
	 *
	 * @return string
	 */
	public function url() {
		return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
	}

	/**
	 * Get the full URL for the request.
	 *
	 * @return string
	 */
	public function fullUrl() {
		$query = $this->getQueryString();

		return $query ? $this->url() . '?' . $query : $this->url();
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

	public function isWechatApp() {
		$ua = $this->server->get('HTTP_USER_AGENT');

		if (preg_match('/MicroMessenger\/([\d.]+)/', $ua, $match)) {
			return true;
		}

		return false;
	}

	public function all() {
		$this->request->remove('_method');

		$request = $this->request->all();
		$query = $this->query->all();

		return array_merge($request, $query);
	}
}
