<?php

namespace Eqnote\RedmineMessage;

use Illuminate\Support\ServiceProvider;
use Eqnote\RedmineMessage\Services\RedmineClient;
use Eqnote\RedmineMessage\Commands\SendRedmineMessage;

class RedmineMessageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/redmine-message.php', 'redmine-message');

        $this->app->singleton('redmine-message', function ($app) {
            return new RedmineClient(config('redmine-message'));
        });

        $this->app->alias('redmine-message', RedmineClient::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/redmine-message.php' => config_path('redmine-message.php'),
        ], 'redmine-message-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendRedmineMessage::class,
            ]);
        }
    }
}