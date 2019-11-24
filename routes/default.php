<?php

return [
    'home' => [
        'method' => 'GET',
        'pattern' => '/',
        'handler' => 'Bloatless\Pile\Actions\ShowLogsAction',
    ],

    'store_log' => [
        'method' => 'POST',
        'pattern' => '/api/v1/log',
        'handler' => 'Bloatless\Pile\Actions\StoreLogAction',
    ],
];
