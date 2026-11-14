<?php

return [

    /*
    | CORS для клиентского приложения (ПР11).
    | Адреса клиента задаются в .env: CORS_ALLOWED_ORIGINS=http://127.0.0.1:5500,http://localhost:5500
    | Разрешение для всех источников ('*') не используется.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://127.0.0.1:5500,http://localhost:5500'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 600,

    // Авторизация по Bearer-токену, cookie не используются
    'supports_credentials' => false,

];
