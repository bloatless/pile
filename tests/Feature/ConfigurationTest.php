<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Feature;

use Bloatless\Pile\Pile;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testDbConfigurationIsValidated()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';

        unset($config['db']['dsn']);
        $app = new Pile($config);
        $response = $app->__invoke([], []);
        $this->assertStringContainsString('Invalid database configuration', $response);

        unset($config['db']);
        $app = new Pile($config);
        $response = $app->__invoke([], []);
        $this->assertStringContainsString('Database configuration missing', $response);
    }

    public function testAuthConfigurationIsValidated()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';

        unset($config['auth']['users']);
        $app = new Pile($config);
        $response = $app->__invoke([], []);
        $this->assertStringContainsString('Auth configuration is invalid', $response);

        unset($config['auth']);
        $app = new Pile($config);
        $response = $app->__invoke([], []);
        $this->assertStringContainsString('Auth configuration missing', $response);
    }

    public function testViewConfigurationIsValidated()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';

        unset($config['path_views']);
        $app = new Pile($config);
        $response = $app->__invoke([], []);
        $this->assertStringContainsString('Path to views missing in config', $response);

        $config['path_views'] = 'non_existent_path';
        $app = new Pile($config);
        $response = $app->__invoke([], []);
        $this->assertStringContainsString('Paths to views is invalid', $response);
    }
}
