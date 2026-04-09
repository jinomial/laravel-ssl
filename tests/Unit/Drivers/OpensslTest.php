<?php

namespace Jinomial\LaravelSsl\Tests\Unit\Drivers;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use Jinomial\LaravelSsl\Contracts\Ssl\Driver as DriverContract;
use Jinomial\LaravelSsl\Drivers\Driver;
use Jinomial\LaravelSsl\Drivers\OpenSsl;

const HOST = 'jinomial.com';

uses()->group('drivers', 'openssl');

it('extends Driver::class', function () {
    $client = \Mockery::mock(ClientInterface::class);
    $driver = new OpenSsl('openssl', $client, 'https://example.com');
    expect($driver)->toBeInstanceOf(Driver::class);
});

it('is a Driver interface', function () {
    $implementsDriver = is_a(OpenSsl::class, DriverContract::class, true);
    expect($implementsDriver)->toBeTrue();
});

it('shows a certificate', function () {
    $client = new HttpClient();
    $driver = new OpenSsl('openssl', $client);
    $answer = $driver->show(HOST, '443');
    expect($answer->first())->toBeInstanceOf(\Jinomial\LaravelSsl\Support\Certificate::class);
    expect($answer->first()->getCommonName())->toEqual(HOST);
})->group('network');

it('shows multiple certificates', function () {
    $client = new HttpClient();
    $driver = new OpenSsl('openssl', $client);
    $answer = $driver->show([
        ['host' => HOST, 'port' => '110'],
        ['host' => HOST, 'port' => '443'],
    ]);
    expect($answer->where('port', '443')->first()->getCommonName())->toEqual(HOST);
})->group('network');
