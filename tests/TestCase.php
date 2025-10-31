<?php

namespace ArgusCS\RedmineMessage\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ArgusCS\RedmineMessage\RedmineMessageServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            RedmineMessageServiceProvider::class,
        ];
    }
}