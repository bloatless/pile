<?php

return [
    'logger' => [
        'type' => 'file',
        'path_logs' => __DIR__ . '/../logs',
        'min_level' => 'notice',
    ],

    'db' => [
        // mysql db credentials
        'connections' => [
            'db1' => [
                'driver' => 'sqlite',
                'database' => __DIR__ . '/../storage/database/sample.sqlite',
            ],
        ],

        'default_connection' => 'db1',
    ],

    'renderer' => [
        'path_views' => __DIR__ . '/../resources/views',
        'compile_path' => __DIR__ . '/../cache/compile',
        'view_components' => [

        ],
    ],

    'auth' => [
        // valid api keys allowed to store logs
        'api_keys' => [
            '123123123',
        ],

        'backend' => 'array',

        // users allowed to view logs
        // HINT: passwords need to be generated using "password_hash" method
        'backends' => [
            'array' => [
                'users' => [
                    'foo' => '$2y$10$hJpespHOJUYzFtHIQk57OusBdwIOXz.8tUdbb9j545Meh2wmeshMm',
                ],
            ]
        ]
    ]
];
