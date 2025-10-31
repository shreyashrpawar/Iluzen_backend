<?php
return [
    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['https://ilusion.io'], // 👈 Your Next.js frontend

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // 👈 Important for using cookies
];
