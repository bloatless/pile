<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    // include config files:
    $config = require_once __DIR__ . '/../config/config.php';
    $routes = require_once __DIR__ . '/../routes/default.php';

    // init dependencies:
    $loggerFactory = new \Bloatless\Endocore\Components\Logger\Factory($config);
    $request = new \Bloatless\Endocore\Http\Request($_GET, $_POST, $_SERVER);
    $router = new \Bloatless\Endocore\Components\Router\Router($routes);
    $logger = $loggerFactory->makeFileLogger();
    $exceptionHandler = new \Bloatless\Endocore\Exception\ExceptionHandler($config, $logger, $request);

    // create application:
    $app = new \Bloatless\Endocore\Application(
        $config,
        $request,
        $router,
        $logger,
        $exceptionHandler
    );

    return $app;
} catch (\Exception $e) {
    exit('Error: ' . $e->getMessage());
}
