<?php
/**
 * This file bootstraps/creates you application. Normally there is no need to change this file, but if you want to
 * inject own/other classes this is would be a good place to start.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Bloatless\Endocore\Application;

try {
    // include config files:
    $config = require_once __DIR__ . '/../config/config.php';

    // create application:
    $app = new Application(__DIR__ . '/../');

    // register app components
    $app->addComponent(
        \Bloatless\Endocore\Components\BasicAuth\BasicAuth::class,
        \Bloatless\Endocore\Components\BasicAuth\BasicAuthFactory::class
    );
    $app->addComponent(
        \Bloatless\Endocore\Components\Database\Database::class,
        \Bloatless\Endocore\Components\Database\DatabaseFactory::class
    );
    $app->addComponent(
        \Bloatless\Endocore\Components\PhtmlRenderer\PhtmlRenderer::class,
        \Bloatless\Endocore\Components\PhtmlRenderer\PhtmlRendererFactory::class
    );

    return $app;
} catch (\Throwable $e) {
    exit(sprintf('Error: %s (%s:%d)', $e->getMessage(), $e->getFile(), $e->getLine()));
}
