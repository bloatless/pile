<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Doubles;

use Bloatless\Pile\Pile;

class PileDouble extends Pile
{
    protected string $requestBody = '';

    public function setRequestBody(string $requestBody): void
    {
        $this->requestBody = $requestBody;
    }

    protected function getRequestBody(): string
    {
        return $this->requestBody;
    }
}
