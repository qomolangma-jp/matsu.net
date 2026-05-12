<?php

use Illuminate\Support\Str;

return [

    'driver' => env('SESSION_DRIVER', 'file'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug((string) env('APP_NAME', 'laravel'), '_').'_session'
    ),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    // LIFF は LINE アプリ内のクロスサイト遷移になるため、本番では SameSite=None が必要。
    'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') !== 'local'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', env('APP_ENV') === 'local' ? 'lax' : 'none'),

    // Chromium 系の LIFF/WebView で第三者コンテキストを扱うときの保険。
    'partitioned' => env('SESSION_PARTITIONED_COOKIE', env('APP_ENV') !== 'local'),

];