<?php

namespace Jinomial\LaravelSsl\Tests\Integration\Facades;

use Jinomial\LaravelSsl\Drivers\Driver;
use Jinomial\LaravelSsl\Facades\Ssl;

const HOST = 'jinomial.com';
const ISSUER_HOST = 'http://i.pki.goog/we1.crt';
const ISSUER_CN = 'WE1';

uses()->group('facades');

it('can show ' . HOST, function () {
    $response = Ssl::show(HOST, 443);
    expect($response->first()->getCommonName())->toEqual(HOST);
})->group('network');

it('can get issuer', function () {
    $response = Ssl::show(ISSUER_HOST, 443, [
        \Jinomial\LaravelSsl\Drivers\OpenSsl::OPTION_ID_AD_CAISSUERS => true,
    ]);
    expect($response->first()->getCommonName())->toEqual(ISSUER_CN);
})->group('network');

it('can access the openssl driver by name', function () {
    $openSsl = Ssl::driver('openssl');

    expect($openSsl)->toBeInstanceOf(Driver::class);
});

it('can access the stream driver by name', function () {
    $stream = Ssl::driver('stream');

    expect($stream)->toBeInstanceOf(Driver::class);
});
