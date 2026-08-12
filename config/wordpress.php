<?php

return [
    'host' => env('WP_DB_HOST', '127.0.0.1'),
    'port' => env('WP_DB_PORT', '3306'),
    'username' => env('WP_DB_USER', 'root'),
    'password' => env('WP_DB_PASSWORD', ''),
    'database' => env('WP_DB_NAME', 'wordpress'),
    'table_prefix' => env('WP_TABLE_PREFIX', 'wp_'),
];
