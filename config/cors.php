<?php
return [
    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://127.0.0.1:3000','http://localhost:3000'], // 👈 Your Next.js frontend

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // 👈 Important for using cookies
];
