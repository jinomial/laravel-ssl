<?php

namespace Jinomial\LaravelSsl\Tests\Unit\Drivers;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\PSR7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Jinomial\LaravelSsl\Contracts\Ssl\Driver as DriverContract;
use Jinomial\LaravelSsl\Drivers\Openssl;
use Jinomial\LaravelSsl\Drivers\Driver;
use JsonException;

const HOST = 'jinomial.com';

uses()->group('drivers', 'openssl');

it('extends Driver::class', function () {
    $client = \Mockery::mock(ClientInterface::class);
    $driver = new Openssl('openssl', $client, 'https://example.com');
    expect($driver)->toBeInstanceOf(Driver::class);
});

it('is a Driver interface', function () {
    $implementsDriver = is_a(Openssl::class, DriverContract::class, true);
    expect($implementsDriver)->toBeTrue();
});

it('shows a certificate', function () {
    $client = new HttpClient();
    $driver = new Openssl('openssl', $client);
    $answer = $driver->show(HOST, 443);
    expect($answer[0]['certificate']['subject']['CN'])->toEqual('sni.cloudflaressl.com');
})->group('network');
