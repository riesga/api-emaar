<?php

return [
    'oracle' => [
        'driver' => 'oracle',
        'tns' => env('TNS', ''),
        'host' => env('DB_HOST_ORACLE', ''),
        'port' => env('DB_PORT_ORACLE', '1521'),
        'database' => env('DB_DATABASE_ORACLE', ''),
        'service_name' => env('DB_SERVICE_NAME', ''),
        'username' => env('DB_USERNAME_ORACLE', ''),
        'password' => env('DB_PASSWORD_ORACLE', ''),
        'charset' => env('DB_CHARSET', 'AL32UTF8'),
        'prefix' => env('DB_PREFIX', ''),
        'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
        'edition' => env('DB_EDITION', 'ora$base'),
        'server_version' => env('DB_SERVER_VERSION', '11g'),
        'load_balance' => env('DB_LOAD_BALANCE', 'yes'),
        'max_name_len' => env('ORA_MAX_NAME_LEN', 30),
        'dynamic' => [],
        'sessionVars' => [
            'NLS_TIME_FORMAT' => 'HH24:MI:SS',
            'NLS_DATE_FORMAT' => 'YYYY-MM-DD HH24:MI:SS',
            'NLS_TIMESTAMP_FORMAT' => 'YYYY-MM-DD HH24:MI:SS',
            'NLS_TIMESTAMP_TZ_FORMAT' => 'YYYY-MM-DD HH24:MI:SS TZH:TZM',
            'NLS_NUMERIC_CHARACTERS' => '.,',
        ],
    ],
];
