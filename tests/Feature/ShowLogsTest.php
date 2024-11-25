<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Feature;

use Bloatless\Pile\Tests\Doubles\PileDouble;
use PHPUnit\Framework\TestCase;

class ShowLogsTest extends TestCase
{
    public function testLogsAreShown()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
        ]);

        $this->assertStringContainsString('Some debug message...', $response);
    }

    public function testFiltersAreShown()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
        ]);

        // Source filter is shown
        $this->assertStringContainsString('<input type="checkbox" name="s[]" value="Test"', $response);

        // Level filer is shown
        $this->assertStringContainsString('<input type="checkbox" name="l[]" value="100"', $response);
    }
}
