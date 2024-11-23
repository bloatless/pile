<?php

declare(strict_types=1);

namespace Bloatless\Pile\Exceptions;

class HttpBadRequestException extends \Exception
{
    protected $code = 400;

    protected $message = 'Error 400: Bad Request.';
}
