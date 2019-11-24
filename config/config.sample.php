<?php

return [
    'logger' => [
        // path to logs directory
        'path_logs' => __DIR__ . '/../logs',

        // min log level
        'min_level' => 'warning',
    ],

    'db' => [
        // mysql db credentials
        'connections' => [
            'db1' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'database' => 'pile',
                'username' => '',
                'password' => '',
                'charset' => 'utf8', // Optional
                'timezone' => 'Europe/Berlin', // Optional
            ],
        ],

        'default_connection' => 'db1',
    ],

    // path to view/layout files
    'path_views' => __DIR__ . '/../resources/views',

    'auth' => [
        // valid api keys allowed to store logs
        'api_keys' => [],

        // users allowed to view logs
        // HINT: passwords need to be generated using "password_hash" method
        'users' => [
            'foo' => '$2y$10$hJpespHOJUYzFtHIQk57OusBdwIOXz.8tUdbb9j545Meh2wmeshMm',
        ],
    ]
];
