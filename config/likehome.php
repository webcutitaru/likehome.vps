<?php

return [
    'site_base_path' => env('SITE_BASE_PATH', ''),
    'default_locale' => env('APP_DEFAULT_LOCALE', 'ro'),
    'locales' => array_filter(array_map('trim', explode(',', env('APP_LOCALES', 'ro,en,ru')))),
    'site_url' => env('PUBLIC_SITE_URL', env('APP_URL', 'http://localhost')),
    'env' => [
        'APP_ENV' => env('APP_ENV', 'local'),
        'APP_DEBUG' => env('APP_DEBUG', 'true'),
        'APP_TIMEZONE' => env('APP_TIMEZONE', 'Europe/Chisinau'),
        'DB_HOST' => env('DB_HOST', '127.0.0.1'),
        'DB_NAME' => env('DB_DATABASE', 'likehome_db'),
        'DB_USER' => env('DB_USERNAME', 'root'),
        'DB_PASS' => env('DB_PASSWORD', ''),
        'DB_CHARSET' => env('DB_CHARSET', 'utf8mb4'),
        'SITE_BASE_PATH' => env('SITE_BASE_PATH', ''),
        'PUBLIC_SITE_URL' => env('PUBLIC_SITE_URL', env('APP_URL', 'http://localhost')),
        'APP_LOCALES' => env('APP_LOCALES', 'ro,en,ru'),
        'APP_DEFAULT_LOCALE' => env('APP_DEFAULT_LOCALE', 'ro'),
    ],
];
