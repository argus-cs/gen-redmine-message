<?php

namespace ArgusCS\RedmineMessage\Services;

use Illuminate\Support\Facades\Http;

class GeminiClient
{

    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('messages.gemini.url');
    }

    /**
     * Sends a HTTP request to the Gemini API with a specified prompt and optionally invokes a callback with the response.
     *
     * @param string $prompt The text to send in the request.
     * @param Closure|null $callback An optional callback to handle the response.
     *
     * @return mixed The HTTP response if no callback is provided.
     * @throws Exception If the request to the Gemini API fails.
     *
     */
    public function generate(string $prompt, ?Closure $callback = null): mixed
    {
        $response = Http::withHeaders([
            'x-goog-api-key' => config('messages.gemini.key'),
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl, [
            'contents' => [
                'parts' => [
                    'text' => $prompt,
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new Exception('Erro ao conectar com o Gemini: ' . $response->body());
        }

        if (!empty($callback)) {
            return $callback($response);
        } else {
            return $response;
        }
    }
}