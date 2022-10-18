<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * This is where your application gets initialized and executed.
 * You should point the document root of your webserver/vhost to this directory an rewrite all requests to this file.
 */

/** @var \Bloatless\Endocore\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';
$app->handle(
    new \Bloatless\Endocore\Core\Http\Request($_GET, $_POST, $_SERVER)
);
