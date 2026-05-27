<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    |--------------------------------------------------------------------------
    */

    // Cho phép tất cả các route API, route của ảnh (storage) và cơ chế bảo mật Sanctum
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*', '*'],

    // Cho phép tất cả các phương thức HTTP (GET, POST, PUT, DELETE, OPTIONS)
    'allowed_methods' => ['*'],

    // Mở khóa hoàn toàn cho mọi Origin kết nối (Kể cả từ cổng 5173 của React)
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    // Cho phép truyền mọi Headers lên kèm request (Content-Type, Authorization, X-Requested-With,...)
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Chuyển thành true nếu hệ thống của bạn cần gửi kèm Cookie hoặc Token qua Header (Cực kỳ an toàn)
    'supports_credentials' => true,

];