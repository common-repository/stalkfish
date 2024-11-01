<?php

namespace Stalkfish\ErrorTracker\Context;

use Stalkfish\Dependencies\Symfony\Component\HttpFoundation\Request;
use Stalkfish\Dependencies\Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

class Request_Context implements Context_Interface {

	/** @var Request|null */
	protected $request;

	public function __construct( Request $request = null ) {
		$this->request = $request ?? Request::createFromGlobals();
	}

	public function getRequest(): array {
		return array(
			'url'       => $this->request->getUri(),
			'ip'        => $this->request->getClientIp(),
			'method'    => $this->request->getMethod(),
			'useragent' => $this->request->headers->get( 'User-Agent' ),
		);
	}

	public function getSession(): array {
		try {
			$session = $this->request->getSession();
		} catch ( \Exception $exception ) {
			$session = array();
		}

		return $session ? $this->getValidSessionData( $session ) : array();
	}

	/**
	 * @param SessionInterface $session
	 * @return array
	 */
	protected function getValidSessionData( $session ): array {
		try {
			json_encode( $session->all() );
		} catch ( Throwable $e ) {
			return array();
		}

		return $session->all();
	}

	public function getCookies(): array {
		return $this->request->cookies->all();
	}

	public function getHeaders(): array {
		return $this->request->headers->all();
	}

	public function getRequestData(): array {
		return array(
			'queryString' => $this->request->query->all(),
			'body'        => $this->request->request->all(),
		);
	}

	public function toArray(): array {
		return array_merge(
			$this->getRequest(),
			array(
			'data'    => $this->getRequestData(),
			'headers' => $this->getHeaders(),
			'cookies' => $this->getCookies(),
			'session' => $this->getSession(),
		));
	}
}
