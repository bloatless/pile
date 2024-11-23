<?php

declare(strict_types=1);

namespace Bloatless\Pile\Exceptions;

class HttpMethodNotAllowedException extends \Exception
{
    protected $code = 405;

    protected $message = 'Error 405: Method not allowed.';
}
