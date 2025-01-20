<?php
return [
    'paths' => ['api/*', 'v1/*', 'sanctum/csrf-cookie'], // مسیرهایی که CORS روی آنها اعمال می‌شود

    'allowed_methods' => ['*'], // همه متدها (GET, POST, PUT, DELETE و غیره)

    'allowed_origins' => ['http://localhost:8080', 'https://app.noticapp.ir'], // آدرس کلاینت (فرانت‌اند)

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // همه هدرها

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // اگر نیاز به ارسال کوکی‌ها دارید، این مقدار را true کنید
];
