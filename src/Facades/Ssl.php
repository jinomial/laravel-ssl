<?php

namespace Jinomial\LaravelSsl\Facades;

use Illuminate\Support\Facades\Facade;
use Jinomial\LaravelSsl\SslManager;

/**
 * @see \Jinomial\LaravelSsl\LaravelSsl
 */
/**
 * @method static \Jinomial\LaravelSsl\Support\CertificateCollection show(string|array $host, string $port = '443', array $options = [])
 * @method static \Jinomial\LaravelSsl\Drivers\Driver driver(string|null $name = null)
 * @method static \Jinomial\LaravelSsl\Drivers\File file()
 * @method static \Jinomial\LaravelSsl\Drivers\OpenSsl openssl()
 * @method static \Jinomial\LaravelSsl\Drivers\Stream stream()
 *
 * @see \Jinomial\LaravelSsl\SslManager
 */
final class Ssl extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor()
    {
        return SslManager::class;
    }
}
