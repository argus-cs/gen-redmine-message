<?php

namespace ArgusCS\RedmineMessage\Commands;

use File;
use Illuminate\Console\Command;
use ArgusCS\RedmineMessage\Services\GitClient;
use ArgusCS\RedmineMessage\Services\GeminiClient;
use ArgusCS\RedmineMessage\Services\RedmineClient;
use ArgusCS\RedmineMessage\Exceptions\CommandWarningException;

class GenMessage extends Command
{
    protected RedmineClient $redmineClient;
    protected GitClient $gitClient;
    protected GeminiClient $geminiClient;

    protected ?string $diff;
    protected ?string $guideline;
    protected string $guidelinePath;
    protected ?string $ticket;
    protected ?string $task;
    protected ?string $prompt;
    protected ?string $journals;

    protected $signature = 'gen:redmine-message {diff?}';

    protected $description = 'Gera mensagens para o Redmine baseadas no git diff, guideline e na descricao da tarefa.';

    public function __construct(
        ?RedmineClient $redmineClient = null,
        ?GitClient $gitClient = null,
        ?GeminiClient $geminiClient = null
    ) {
        $this->redmineClient = $redmineClient ?? new RedmineClient();
        $this->gitClient = $gitClient ?? new GitClient();
        $this->geminiClient = $geminiClient ?? new GeminiClient();

        $this->guidelinePath = config('messages.redmine.guideline');

        parent::__construct();
    }

    public function handle(): void
    {
        try {
            $this->info("Iniciando geração de mensagem");
            $this->ticket = $this->ask("Qual o numero da tarefa do Redmine?");

            $this->info("Buscando detalhes da tarefa...");
            $this->taskDetails();

            $this->info("Detalhes da tarefa: \n");
            $this->line($this->task);

            if (!$this->confirm('Deseja continuar?', true)) {
                $this->info('Operacao cancelada.');
                return;
            }

            $this->info('Buscando diff...');
            $diffSulfix = $this->argument('diff') ?? '--staged';
            $this->diff = $this->gitClient->diff($diffSulfix);

            if (empty(trim($this->diff))) {
                $this->warn("Sem alterações encontradas, certifique-se de ter arquivos staged (git add ...)");
                return;
            }

            $this->info('Buscando diretrizes de mensagens do Redmine...');
            $this->setGuideline();

            $this->info('Gerando prompt...');
            $this->generatePrompt();

            $this->info("Aguardando resposta do Gemini...");

            $this->geminiClient->generate($this->prompt, function ($response) {
                $this->info('Gerado com sucesso!');
                $message = $this->getContent($response);

                $this->info("Mensagem gerada: \n");
                $this->line($message);

                $this->newLine();

                if ($this->confirm('Deseja enviar a mensagem para o Redmine?')) {
                    $this->redmineClient->addTaskNote($this->ticket, $message);
                    $this->info('Mensagem enviada para o Redmine com sucesso!');
                }
            });

        } catch(CommandWarningException $exception) {
            $this->warn($exception->getMessage());
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    protected function taskDetails(): void
    {
        $task = $this->redmineClient->task($this->ticket, 'issue');

        $this->task = $task['subject'] ."\n\n". $task['description'];

        if (!empty($task['journals'])) {
            $this->journals = "Comentarios anteriores: \n";
            $this->journals .= implode("\n", collect($task['journals'])->pluck('notes')->toArray());
        }
    }

    protected function setGuideline(): void
    {
        if (!File::exists($this->guidelinePath)) {
            throw new CommandWarningException("Arquivo de diretrizes de mensagem do Redmine não encontrado em: {$this->guidelinePath}. Gerando sugestões sem restrições.");
        }

        $this->guideline = File::get($this->guidelinePath);
    }

    protected function generatePrompt(): void
    {
        $prompt = "Com base nas seguintes alterações de código, gere mensagem para o Redmine. \n". $this->diff;
        $prompt .= "\n\nSeguindo rigorosamente as seguintes diretrizes de mensagem de commit:\n" . $this->guideline;
        $prompt .= $this->ticket ? "\n\nTicket: " . $this->ticket : '';
        $prompt .= $this->task ? "\n\nDetalhes da tarefa: " . $this->task : '';
        $prompt .= $this->journals ?? '';
        $prompt .= "\n\nNome da branch: ". $this->gitClient->nameBranch();
        $prompt .= "\n\nO Commit ainda não foi feito (omitir essa informação).";

        $prompt .= "Formatar a mensagem para as notas do Redmine, fazendo uso completo de titulos(h2 pra baixo) listas e outros.";
        $prompt .= "Retorne apenas a mensagem.";

        $this->prompt = $prompt;
    }

    private function getContent($response): string
    {
        $result = $response->json('candidates')[0];
        $result = $result['content']['parts'][0]['text'];

        return $this->cleanOutput($result);
    }

    private function cleanOutput(string $content): string
    {
        // Remove ```<linguagem>\n no início
        $cleaned = preg_replace('/^```[a-zA-Z]*\s*\n/', '', $content);
        // Remove ```\n no início sem linguagem
        $cleaned = preg_replace('/^```\n/', '', $cleaned);
        // Remove \n``` no final
        $cleaned = preg_replace('/\n```$/', '', $cleaned);

        return trim($cleaned);
    }
}