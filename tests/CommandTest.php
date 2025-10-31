<?php

namespace ArgusCS\RedmineMessage\Tests;

use Illuminate\Support\Facades\Http;

class CommandTest extends TestCase
{
    public function test_artisan_redmine_send_success(): void
    {
        $endpoint = rtrim(config('redmine-message.endpoint'), '/');

        Http::fake([
            $endpoint . '/issues.json' => Http::response(['issue' => ['id' => 3]], 201),
        ]);

        $this->artisan('redmine:send', [
            'subject' => 'Assunto',
            'message' => 'Mensagem',
            '--project' => 1,
        ])
            ->expectsOutput('Mensagem enviada com sucesso.')
            ->assertExitCode(0);
    }
}