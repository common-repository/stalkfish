<?php

namespace Stalkfish\ErrorTracker\Http\Exceptions;

use Exception;
use Stalkfish\ErrorTracker\Http\Response;

class Bad_Response extends Exception {

	/** @var Stalkfish\ErrorTracker\Http\Response */
	public $response;

	public static function createForResponse( Response $response ) {
		$exception = new static( "Could not perform request because: {$response->getError()}" );

		$exception->response = $response;

		return $exception;
	}
}
