<?php

namespace Jinomial\LaravelSsl\Tests\Integration\Facades;

use Jinomial\LaravelSsl\Facades\Ssl;

const HOST = 'jinomial.com';

uses()->group('facades');

it('can show ' . HOST, function () {
    $response = Ssl::show(HOST, 443);
    expect($response[0]['certificate']['subject']['CN'])->toEqual('sni.cloudflaressl.com');
})->group('network');
