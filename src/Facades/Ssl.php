<?php

namespace Jinomial\LaravelSsl\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jinomial\LaravelSsl\LaravelSsl
 */
class Ssl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ssl';
    }
}
