<?php

namespace Stalkfish\ErrorTracker\Http\Exceptions;

use Stalkfish\ErrorTracker\Http\Response;

class Invalid_Data extends Bad_Response_Code {

	public static function getMessageForResponse( Response $response ) {
		return 'Invalid data found';
	}
}
