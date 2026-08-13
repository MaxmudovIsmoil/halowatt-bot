<?php
return [
    // Node.js bot ichki API
    'bot' => [
        'url' => env('BOT_API_URL', 'http://127.0.0.1:4000'),
        'secret' => env('BOT_API_SECRET', ''),
    ],
];
