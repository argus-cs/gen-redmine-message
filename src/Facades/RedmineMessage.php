<?php

namespace ArgusCS\RedmineMessage\Facades;

use Illuminate\Support\Facades\Facade;

class RedmineMessage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'messages';
    }
}