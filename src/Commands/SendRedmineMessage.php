<?php

namespace Eqnote\RedmineMessage\Commands;

use Illuminate\Console\Command;
use Eqnote\RedmineMessage\Services\RedmineClient;

class SendRedmineMessage extends Command
{
    protected $signature = 'gen:redmine-message';

    protected $description = 'Envia uma mensagem/issue para o Redmine usando a config do pacote';

    public function handle(RedmineClient $client): int
    {
        $subject = (string) $this->argument('subject');
        $message = (string) $this->argument('message');
        $project = $this->option('project');

        $result = $client->send($subject, $message, ['project' => $project]);

        if (($result['status'] ?? 'error') === 'ok') {
            $this->info('Mensagem enviada com sucesso.');
            return Command::SUCCESS;
        }

        $this->error('Falha ao enviar: ' . ($result['error'] ?? json_encode($result)));
        return Command::FAILURE;
    }
}