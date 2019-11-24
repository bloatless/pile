<?php

/**
 * This is where your application gets initialized and executed.
 * You should point the document root of your webserver/vhost to this directory an rewrite all requests to this file.
 */

$app = require __DIR__ . '/../bootstrap/app.php';
$app->run();
