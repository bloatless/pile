<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Feature;

use Bloatless\Pile\Tests\Doubles\PileDouble;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    public function testHomepageRequiresAuthorization()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]);

        $this->assertStringContainsString('Authorization required.', $response);
    }

    public function testLoginIsPossibleWithValidCredentials()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:bar'),
        ]);

        $this->assertStringContainsString('<div class="container">', $response);
    }

    public function testLoginIsNotPossibleWithInvalidCredentials()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('foo:baz'),
        ]);

        $this->assertStringContainsString('Authorization required.', $response);
    }

    public function testApiRequestIsPossibleWithValidKey()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);
        $app->setRequestBody('[]');

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/v1/log',
            'HTTP_X_API_KEY' => '123123123',
        ]);

        $this->assertStringContainsString('Error: Invalid data', $response);
    }

    public function testApiRequestIsNotPossibleWithInvalidKey()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/v1/log',
            'HTTP_X_API_KEY' => 'non-existent-key',
        ]);

        $this->assertStringContainsString('Error 401: Unauthorized', $response);
    }
}
