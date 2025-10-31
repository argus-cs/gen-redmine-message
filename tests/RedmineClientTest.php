<?php

namespace ArgusCS\RedmineMessage\Tests;

use Illuminate\Support\Facades\Http;
use ArgusCS\RedmineMessage\Services\RedmineClient;

class RedmineClientTest extends TestCase
{
    public function test_returns_disabled_when_feature_off(): void
    {
        config(['redmine-message.enabled' => false]);

        $client = app(RedmineClient::class);
        $result = $client->send('Assunto', 'Mensagem');

        $this->assertSame('disabled', $result['status']);
    }

    public function test_send_success_with_http_fake(): void
    {
        $endpoint = rtrim(config('redmine-message.endpoint'), '/');

        Http::fake([
            $endpoint . '/issues.json' => Http::response(['issue' => ['id' => 1]], 201),
        ]);

        $client = app(RedmineClient::class);
        $result = $client->send('Assunto', 'Mensagem', ['project' => 1]);

        $this->assertSame('ok', $result['status']);
        $this->assertSame(201, $result['code']);
        $this->assertIsArray($result['body']);
    }

    public function test_facade_send_success(): void
    {
        $endpoint = rtrim(config('redmine-message.endpoint'), '/');

        Http::fake([
            $endpoint . '/issues.json' => Http::response(['issue' => ['id' => 2]], 201),
        ]);

        $result = \Eqnote\RedmineMessage\Facades\RedmineMessage::send('Assunto', 'Mensagem');

        $this->assertSame('ok', $result['status']);
        $this->assertSame(201, $result['code']);
    }
}