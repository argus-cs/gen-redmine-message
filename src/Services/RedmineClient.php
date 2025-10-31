<?php

namespace ArgusCS\RedmineMessage\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class RedmineClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('messages.redmine.url');
    }

    /**
     * Retrieves a specific task from the Redmine API.
     *
     * @param string $ticket The ticket ID for the task to be retrieved.
     * @param string|null $key The specific key within the JSON response to extract, or null for the full response.
     */
    public function task(string $ticket, ?string $key = null): mixed
    {
        $url = $this->baseUrl . '/issues/' . $ticket . '.json';

        return Http::withHeaders([
            'X-Redmine-API-Key' => config('messages.redmine.key'),
            'Content-Type' => 'application/json'
        ])
            ->get($url, ['include' => 'journals'])
            ->json($key);
    }

    /**
     * Adds a note to an existing task in the Redmine API.
     *
     * @param string $ticket The ticket ID of the task to which the note should be added.
     * @param string $note The note content to be added to the task.
     * @return PromiseInterface|Response The HTTP response or a promise representing the asynchronous result.
     */
    public function addTaskNote(string $ticket, string $note): PromiseInterface|Response
    {
        $url = $this->baseUrl . '/issues/'. $ticket .'.json';

        return Http::withHeaders([
            'X-Redmine-API-Key' => config('messages.redmine.key'),
            'Content-Type' => 'application/json'
        ])->put($url, [
            'issue' => [
                'notes' => $note,
            ]
        ]);
    }
}