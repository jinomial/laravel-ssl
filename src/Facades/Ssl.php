<?php

namespace Jinomial\LaravelSsl\Facades;

use Illuminate\Support\Facades\Facade;
use Jinomial\LaravelSsl\SslManager;

/**
 * @see \Jinomial\LaravelSsl\LaravelSsl
 */
class Ssl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SslManager::class;
    }
}
