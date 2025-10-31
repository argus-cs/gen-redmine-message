<?php

return [
    'gemini' => [
        'url' => env('GEMINI_URL', ''),
        'key' => env('GEMINI_API_KEY', ''),
    ],

    'redmine' => [
        'url' => env('REDMINE_URL', ''),
        'key' => env('REDMINE_API_KEY', ''),
        'guideline' => env('REDMINE_GUIDELINE', ''),
    ],
];