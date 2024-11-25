<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Feature;

use Bloatless\Pile\Tests\Doubles\PileDouble;
use PHPUnit\Framework\TestCase;

class StoreLogTest extends TestCase
{
    public function testValidLogCanBeStored()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);
        $logData = file_get_contents(PATH_TESTS . '/Fixtures/log_entry.json');
        $app->setRequestBody($logData);

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/v1/log',
            'HTTP_X_API_KEY' => '123123123',
        ]);

        $this->assertStringContainsString('"log_id":', $response);
    }

    public function testInvalidLogCanNotBeStored()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);
        $logData = file_get_contents(PATH_TESTS . '/Fixtures/log_entry.json');
        $logData = json_decode($logData, true);
        unset($logData['data']['type']);
        $app->setRequestBody(json_encode($logData));

        $response = $app->__invoke([], [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/v1/log',
            'HTTP_X_API_KEY' => '123123123',
        ]);

        $this->assertStringContainsString('Error: Invalid data.', $response);
    }
}