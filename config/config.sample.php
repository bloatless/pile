<?php

return [
    // number of logs per page
    'logs_per_page' => 50,

    // delete old logs after n days
    'keep_logs_days' => 360,

    // path to template files (needs no adjustment by default)
    'path_views' => __DIR__ . '/../resources/views',

    // adjust database credentials
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=pile',
        'username' => '',
        'password' => '',
    ],

    'auth' => [
        // valid api keys allowed to store logs
        'api_keys' => [
            // add api keys
            // 'my-secret-api-key',
            // 'another-valid-api-key',
            // ...
        ],

        // add users accounts (to view logs)
        // passwords need to be encrypted using password_hash method
        // Example: php -r "echo password_hash('my-password', PASSWORD_DEFAULT);"
        'users' => [
            // add users
            // 'user1' => 'password-hash1',
            // 'user2' => 'password-hash2',
            // ...
        ],
    ],
];
