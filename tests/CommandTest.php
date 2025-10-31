<?php

namespace ArgusCS\RedmineMessage\Tests;

use ArgusCS\RedmineMessage\Commands\GenMessage;

class CommandTest extends TestCase
{
    public function test_command_signature_and_description(): void
    {
        $command = new GenMessage();

        $this->assertSame('gen:redmine-message', $command->getSignature());
        $this->assertStringContainsString('Gera mensagens para o Redmine', (string) $command->getDescription());
    }

    public function test_command_is_registered_in_kernel(): void
    {
        $this->artisan('help', ['command_name' => 'gen:redmine-message'])
            ->assertExitCode(0);
    }
}