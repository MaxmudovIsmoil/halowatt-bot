<?php

use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'Halowatt Admin'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Tashkent'),
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => ['driver' => 'file'],
    'providers' => ServiceProvider::defaultProviders()->toArray(),
];
