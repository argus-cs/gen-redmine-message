<?php

namespace ArgusCS\RedmineMessage;

use ArgusCS\RedmineMessage\Commands\GenMessage;
use Illuminate\Support\ServiceProvider;
use Eqnote\RedmineMessage\Services\RedmineClient;
use Eqnote\RedmineMessage\Commands\SendRedmineMessage;

class RedmineMessageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/messages.php', 'messages');

//        $this->app->singleton('messages', function ($app) {
//            return new RedmineClient();
//        });

//        $this->app->alias('messages', RedmineClient::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/messages.php' => config_path('messages.php'),
        ], 'messages-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenMessage::class
            ]);
        }
    }
}