<?php

declare(strict_types=1);

namespace Bloatless\Pile\Exceptions;

class DatabaseException extends \Exception
{
    protected $code = 500;

    protected $message = 'Error 500: Database Error';
}
