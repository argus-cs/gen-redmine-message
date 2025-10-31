<?php

namespace ArgusCS\RedmineMessage;

use Illuminate\Support\ServiceProvider;
use ArgusCS\RedmineMessage\Commands\GenMessage;
use ArgusCS\RedmineMessage\Commands\SendRedmineMessage;

class RedmineMessageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/messages.php', 'messages');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/messages.php' => config_path('messages.php'),
        ], 'messages');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenMessage::class
            ]);
        }
    }
}