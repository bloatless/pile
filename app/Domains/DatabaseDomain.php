<?php

declare(strict_types=1);

namespace Bloatless\Pile\Domains;

use Bloatless\Endocore\Components\Database\Database;

abstract class DatabaseDomain
{
    protected Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }
}
