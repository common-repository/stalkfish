<?php

namespace Stalkfish\ErrorTracker\Http\Exceptions;

use Exception;
use Stalkfish\ErrorTracker\Http\Response;

class Bad_Response_Code extends Exception {

	/** @var Response */
	public $response;

	/** @var array */
	public $errors;

	public static function createForResponse( Response $response ) {
		$exception = new static( static::getMessageForResponse( $response ) );

		$exception->response = $response;

		$bodyErrors = isset( $response->getBody()['errors'] ) ? $response->getBody()['errors'] : array();

		$exception->errors = $bodyErrors;

		return $exception;
	}

	public static function getMessageForResponse( Response $response ) {
		return "Response code {$response->getHttpResponseCode()} returned";
	}
}
