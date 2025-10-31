<?php

namespace ArgusCS\RedmineMessage\Services;

use Symfony\Component\Process\Process;

class GitClient
{
    /**
     * Executes a Git diff command with the specified suffix.
     *
     * @param string $sulfix The suffix to append to the git diff command. Defaults to '--staged'.
     * @return string The output of the diff command.
     * @throws Exception If the git diff process fails.
     */
    public function diff(string $sulfix = '--staged'): string
    {
        $process = new Process(['git', 'diff', $sulfix]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception('Erro ao executar o git diff: ' . $process->getErrorOutput());
        }

        return $process->getOutput();
    }

    public function nameBranch(): string
    {
        $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception('Erro ao obter o nome da branch: ' . $process->getErrorOutput());
        }

        return $process->getOutput();
    }
}