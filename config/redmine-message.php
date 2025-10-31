<?php

return [
    // Endpoint base da API do Redmine (ex.: https://redmine.seudominio.com)
    'endpoint' => env('REDMINE_ENDPOINT', 'https://redmine.example.com'),

    // Token de API do Redmine
    'api_token' => env('REDMINE_API_TOKEN', ''),

    // Timeout de requisições
    'timeout' => env('REDMINE_TIMEOUT', 10),
];