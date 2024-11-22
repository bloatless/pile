<?php

declare(strict_types=1);

namespace Bloatless\Pile\Exceptions;

class HttpUnauthorizedException extends \Exception
{
    protected $code = 401;

    protected $message = 'Error 401: Unauthorized.';
}
