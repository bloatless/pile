<?php

const PATH_ROOT = __DIR__ . '/..';
const PATH_TESTS = __DIR__;

require_once PATH_ROOT . '/src/Pile.php';
require_once PATH_TESTS . '/Fixtures/DatabaseFixture.php';
require_once PATH_TESTS . '/Doubles/PileDouble.php';

// SetUp database
$config = include PATH_ROOT . '/config/config.testing.php';
$dbSetup = new \Bloatless\Pile\Tests\Fixtures\DatabaseFixture($config);
$dbSetup->__invoke();
