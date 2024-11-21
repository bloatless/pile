<?php

$pathToConfig = __DIR__ . '/../config/config.php';

if (!file_exists($pathToConfig)) {
    exit('Error: Config file not found.');
}

require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config/config.php';

$pile = new \Bloatless\Pile\Pile($config);
$pile->__invoke($_REQUEST, $_SERVER);
