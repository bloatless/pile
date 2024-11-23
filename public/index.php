<?php

$appFile = __DIR__ . '/../src/Pile.php';
if (!file_exists($appFile)) {
    exit(sprintf('Error: App file not found. (Expected at: %s).', $appFile));
}

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    exit(sprintf('Error: Config file not found. (Expected at: %s).', $configFile));
}

require_once $appFile;
$config = include $configFile;

$pile = new \Bloatless\Pile\Pile($config);
$pile->__invoke($_REQUEST, $_SERVER);
