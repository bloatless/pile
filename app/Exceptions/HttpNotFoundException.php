<?php

declare(strict_types=1);

namespace Bloatless\Pile\Exceptions;

class HttpNotFoundException extends \Exception
{
    protected $code = 404;

    protected $message = 'Error 404: Not found.';
}
