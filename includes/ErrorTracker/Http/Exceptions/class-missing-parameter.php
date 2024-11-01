<?php

namespace Stalkfish\ErrorTracker\Http\Exceptions;

use Exception;

class Missing_Parameter extends Exception
{
    public static function create(string $parameterName)
    {
        return new static("`$parameterName` is a required parameter");
    }
}
