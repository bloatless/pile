<?php

require_once __DIR__ . '/../vendor/autoload.php';


try {
    // include config files:
    $config = require_once __DIR__ . '/../config/config.php';



    return $app;
} catch (\Throwable $e) {
    exit(sprintf('Error: %s (%s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
}
