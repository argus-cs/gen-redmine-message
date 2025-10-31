<?php

namespace Eqnote\RedmineMessage\Facades;

use Illuminate\Support\Facades\Facade;

class RedmineMessage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'redmine-message';
    }
}