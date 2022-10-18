<?php

return [
    'home' => [
        'method' => 'GET',
        'pattern' => '/',
        'handler' => 'Bloatless\Pile\Actions\Website\ShowLogsAction',
    ],

    'store_log' => [
        'method' => 'POST',
        'pattern' => '/api/v1/log',
        'handler' => 'Bloatless\Pile\Actions\Api\StoreLogAction',
    ],
    'get_log_stats' => [
        'method' => 'GET',
        'pattern' => '/api/v1/logstats',
        'handler' => 'Bloatless\Pile\Actions\Api\GetLogStatsAction',
    ],
];
