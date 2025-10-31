<?php

namespace ArgusCS\RedmineMessage\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Filesystem\Filesystem;
use ArgusCS\RedmineMessage\Services\GitClient;
use ArgusCS\RedmineMessage\Services\GeminiClient;

class GenMessageE2ETest extends TestCase
{
    /**
     * Fake Git client to avoid running local git processes during tests.
     */
    private function fakeGit(string $diff, string $branch = 'feature/test-branch'): void
    {
        $fake = new class($diff, $branch) extends GitClient {
            public function __construct(private string $diff, private string $branch) {}
            public function diff(string $sulfix = '--staged'): string { return $this->diff; }
            public function nameBranch(): string { return $this->branch; }
        };

        $this->app->instance(GitClient::class, $fake);
    }

    /**
     * Fake Gemini client to short-circuit HTTP and return a controlled message text.
     */
    private function fakeGemini(string $message = 'Mensagem gerada pelo Gemini'): void
    {
        $fake = new class($message) extends GeminiClient {
            public function __construct(private string $message) {}
            public function generate(string $prompt, ?\Closure $callback = null): mixed
            {
                $response = Http::response([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [ [ 'text' => $this->message ] ],
                            ],
                        ],
                    ],
                ], 200);

                return $callback ? $callback($response) : $response;
            }
        };

        $this->app->instance(GeminiClient::class, $fake);
    }

    public function test_happy_path_generates_and_sends_note(): void
    {
        config([
            'messages.redmine.url' => 'https://redmine.example.com',
            'messages.redmine.key' => 'dummy-key',
        ]);

        // Prepare guideline temp file
        $fs = new Filesystem();
        $guidelinePath = base_path('guideline.tmp.md');
        $fs->put($guidelinePath, "## Diretrizes\n- Regra 1\n- Regra 2");
        config(['messages.redmine.guideline' => $guidelinePath]);

        // Fakes
        $this->fakeGit("diff --git a/file.php b/file.php\n+ new line", 'feature/abc-123');
        $this->fakeGemini('Mensagem gerada pelo Gemini');

        // HTTP fakes for Redmine
        Http::fake([
            'https://redmine.example.com/issues/123.json*' => Http::response([
                'issue' => [
                    'id' => 123,
                    'subject' => 'Sample issue',
                    'description' => 'Issue description',
                    'journals' => [ ['notes' => 'First note'], ['notes' => 'Second note'] ],
                ],
            ], 200),
            'https://redmine.example.com/issues/123.json' => Http::response(['status' => 'updated'], 200),
        ]);

        $this->artisan('gen:redmine-message')
            ->expectsQuestion('Qual o numero da tarefa do Redmine?', '123')
            ->expectsOutputToContain('Buscando detalhes da tarefa...')
            ->expectsOutputToContain('Detalhes da tarefa:')
            ->expectsConfirmation('Deseja continuar?', 'yes')
            ->expectsOutputToContain('Buscando diff...')
            ->expectsOutputToContain('Buscando diretrizes de mensagens do Redmine...')
            ->expectsOutputToContain('Gerando prompt...')
            ->expectsOutputToContain('Aguardando resposta do Gemini...')
            ->expectsOutputToContain('Gerado com sucesso!')
            ->expectsOutputToContain('Mensagem gerada:')
            ->expectsConfirmation('Deseja enviar a mensagem para o Redmine?', 'yes')
            ->expectsOutputToContain('Mensagem enviada para o Redmine com sucesso!')
            ->assertExitCode(0);

        // Assert Redmine GET
        Http::assertSent(function ($request) {
            if ($request->method() !== 'GET') return false;
            return str_contains($request->url(), '/issues/123.json')
                && str_contains($request->url(), 'include=journals')
                && $request->hasHeader('X-Redmine-API-Key', 'dummy-key');
        });

        // Assert Redmine PUT
        Http::assertSent(function ($request) {
            if ($request->method() !== 'PUT') return false;
            return $request->url() === 'https://redmine.example.com/issues/123.json'
                && $request->hasHeader('X-Redmine-API-Key', 'dummy-key')
                && $request->data() === [ 'issue' => [ 'notes' => 'Mensagem gerada pelo Gemini' ] ];
        });

        // Cleanup
        $fs->delete($guidelinePath);
    }

    public function test_stops_when_diff_is_empty(): void
    {
        config([
            'messages.redmine.url' => 'https://redmine.example.com',
            'messages.redmine.key' => 'dummy-key',
        ]);

        // Guideline válida
        $path = base_path('guideline.tmp.md');
        file_put_contents($path, 'Conteudo guideline');
        config(['messages.redmine.guideline' => $path]);

        // Empty diff fake
        $this->fakeGit('');
        $this->fakeGemini('Mensagem gerada pelo Gemini');

        Http::fake(); // nothing should be sent

        $this->artisan('gen:redmine-message')
            ->expectsQuestion('Qual o numero da tarefa do Redmine?', '321')
            ->expectsConfirmation('Deseja continuar?', 'yes')
            ->expectsOutputToContain('Sem alterações encontradas')
            ->assertExitCode(0);

        Http::assertNothingSent();

        @unlink($path);
    }

    public function test_stops_when_guideline_file_is_missing(): void
    {
        config([
            'messages.redmine.url' => 'https://redmine.example.com',
            'messages.redmine.key' => 'dummy-key',
            'messages.redmine.guideline' => base_path('file/that/does/not/exist.md'),
        ]);

        // Git with valid diff
        $this->fakeGit('+ some change');
        $this->fakeGemini('Mensagem gerada pelo Gemini');

        Http::fake(); // nothing should be sent

        $this->artisan('gen:redmine-message')
            ->expectsQuestion('Qual o numero da tarefa do Redmine?', '555')
            ->expectsConfirmation('Deseja continuar?', 'yes')
            ->expectsOutputToContain('Arquivo de diretrizes de mensagem do Redmine não encontrado')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }
}