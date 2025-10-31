<?php

namespace Eqnote\RedmineMessage\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class RedmineClient
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Envia uma issue/mensagem ao Redmine.
     *
     * @param string $subject
     * @param string $message
     * @param array $context [project => id|identifier]
     * @return array
     */
    public function send(string $subject, string $message, array $context = []): array
    {
        if (!Arr::get($this->config, 'enabled', true)) {
            return ['status' => 'disabled'];
        }

        $endpoint = rtrim($this->config['endpoint'] ?? '', '/');
        $token = $this->config['api_token'] ?? '';
        $project = $context['project'] ?? $this->config['default_project'];

        $payload = [
            'issue' => [
                'subject' => $subject,
                'description' => $message,
                'project_id' => $project,
            ],
        ];

        if (!$endpoint || !$token) {
            return ['status' => 'error', 'error' => 'Missing endpoint/api_token'];
        }

        try {
            $response = Http::withHeaders([
                'X-Redmine-API-Key' => $token,
                'Content-Type' => 'application/json',
            ])->timeout($this->config['timeout'] ?? 10)
              ->post($endpoint . '/issues.json', $payload);

            return [
                'status' => $response->successful() ? 'ok' : 'error',
                'code' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
}