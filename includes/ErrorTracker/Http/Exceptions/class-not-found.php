<?php

namespace Stalkfish\ErrorTracker\Http\Exceptions;

use Stalkfish\ErrorTracker\Http\Response;

class Not_Found extends Bad_Response_Code
{
    public static function getMessageForResponse(Response $response)
    {
        return 'Not found';
    }
}
