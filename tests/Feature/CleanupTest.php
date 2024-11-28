<?php

declare(strict_types=1);

namespace Bloatless\Pile\Tests\Feature;

use Bloatless\Pile\Tests\Doubles\PileDouble;
use Bloatless\Pile\Tests\Fixtures\DatabaseFixture;
use PHPUnit\Framework\TestCase;

class CleanupTest extends TestCase
{
    public function testValidLogCanBeStored()
    {
        $config = include PATH_ROOT . '/config/config.testing.php';
        $app = new PileDouble($config);
        $app->runCommand('cleanup');
        $this->expectOutputRegex('/\d rows deleted/');

        // restore db
        $dbFixture = new DatabaseFixture($config);
        $dbFixture->__invoke();
    }
}
