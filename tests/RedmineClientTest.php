<?php

namespace ArgusCS\RedmineMessage\Tests;

use Illuminate\Support\Facades\Http;
use ArgusCS\RedmineMessage\Services\RedmineClient;

class RedmineClientTest extends TestCase
{
    public function test_task_fetches_issue_with_journals_and_returns_key(): void
    {
        config([
            'messages.redmine.url' => 'https://redmine.example.com',
            'messages.redmine.key' => 'dummy-key',
        ]);

        Http::fake([
            // Match GET with query include=journals
            'https://redmine.example.com/issues/123.json*' => Http::response([
                'issue' => [
                    'id' => 123,
                    'subject' => 'Sample issue',
                    'description' => 'Issue description',
                    'journals' => [
                        ['notes' => 'First note'],
                        ['notes' => 'Second note'],
                    ],
                ],
            ], 200),
        ]);

        $client = new RedmineClient();
        $issue = $client->task('123', 'issue');

        $this->assertIsArray($issue);
        $this->assertSame(123, $issue['id']);
        $this->assertSame('Sample issue', $issue['subject']);
        $this->assertSame('Issue description', $issue['description']);
        $this->assertCount(2, $issue['journals']);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/issues/123.json')
                && str_contains($request->url(), 'include=journals')
                && $request->hasHeader('X-Redmine-API-Key', 'dummy-key');
        });
    }

    public function test_add_task_note_puts_note_with_expected_payload(): void
    {
        config([
            'messages.redmine.url' => 'https://redmine.example.com',
            'messages.redmine.key' => 'dummy-key',
        ]);

        Http::fake([
            'https://redmine.example.com/issues/456.json' => Http::response(['status' => 'updated'], 200),
        ]);

        $client = new RedmineClient();
        $response = $client->addTaskNote('456', 'Mensagem gerada automaticamente...');

        $this->assertTrue($response->successful());

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'https://redmine.example.com/issues/456.json'
                && $request->hasHeader('X-Redmine-API-Key', 'dummy-key')
                && $request->data() === [
                    'issue' => [
                        'notes' => 'Mensagem gerada automaticamente...',
                    ],
                ];
        });
    }
}