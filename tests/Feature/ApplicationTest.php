<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Feature;

use Bloatless\Pile\Pile;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testAppCanBeInitialized()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new Pile($config);
        $this->assertInstanceOf(Pile::class, $app);
    }
}
