<?php

namespace Stalkfish\Dependencies\Illuminate\Contracts\Container;

use Exception;
use Stalkfish\Dependencies\Psr\Container\ContainerExceptionInterface;
class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    //
}
