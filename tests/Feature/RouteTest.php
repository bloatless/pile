<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Feature;

use Bloatless\Pile\Pile;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testNotFoundIsReturned()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new Pile($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/invalid-page',
        ]);

        $this->assertStringContainsString('Error 404: Not found', $response);
    }

    public function testMethodNotAllowedIsReturned()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new Pile($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/',
        ]);

        $this->assertStringContainsString('Error 405: Method not allowed', $response);
    }
}
