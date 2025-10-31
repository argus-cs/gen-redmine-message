<?php

namespace ArgusCS\RedmineMessage\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ArgusCS\RedmineMessage\RedmineMessageServiceProvider;
use ArgusCS\RedmineMessage\Facades\RedmineMessage;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            RedmineMessageServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'RedmineMessage' => RedmineMessage::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('redmine-message.enabled', true);
        $app['config']->set('redmine-message.endpoint', 'https://redmine.example.com');
        $app['config']->set('redmine-message.api_token', 'dummy-token');
        $app['config']->set('redmine-message.default_project', 1);
        $app['config']->set('redmine-message.timeout', 5);
    }
}