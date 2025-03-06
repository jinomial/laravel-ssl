<?php

namespace Jinomial\LaravelSsl\Tests\Unit\Drivers;

use Jinomial\LaravelSsl\Contracts\Ssl\Driver as DriverContract;
use Jinomial\LaravelSsl\Drivers\Driver;
use Jinomial\LaravelSsl\Drivers\Stream;

const HTTPS_HOST = 'jinomial.com';
const ISSUER = 'WE1';
const POP_HOST = 'pop3.comcast.net';
const SMTP_HOST = 'smtp.gmail.com';
const IMAP_HOST = 'imap.comcast.net';
const POP_IMAP_CN = 'imap.email.comcast.net';

uses()->group('drivers', 'stream');

it('extends Driver::class', function () {
    $client = \Mockery::mock(ClientInterface::class);
    $driver = new Stream('stream', []);
    expect($driver)->toBeInstanceOf(Driver::class);
});

it('is a Driver interface', function () {
    $implementsDriver = is_a(Stream::class, DriverContract::class, true);
    expect($implementsDriver)->toBeTrue();
});

it('shows a certificate', function () {
    $driver = new Stream('stream');
    $answer = $driver->show(HOST, '443', [Stream::OPTION_CHAIN => true]);
    expect($answer[0][0]['subject']['CN'])->toEqual(HTTPS_HOST);
})->group('network');

it('shows multiple certificates', function () {
    $driver = new Stream('stream', ['timeout' => 2]);
    $answer = $driver->show([
        ['host' => HOST, 'port' => '110'],
        ['host' => HOST, 'port' => '443'],
    ], '', [Stream::OPTION_CHAIN => true]);
    expect($answer[1][0]['subject']['CN'])->toEqual(HTTPS_HOST);
})->group('network');

it('shows a chain', function () {
    $driver = new Stream('stream');
    $answer = $driver->show(HOST, '443', [Stream::OPTION_CHAIN => true]);
    expect($answer[0][1]['subject']['CN'])->toEqual(ISSUER);
})->group('network');

it('can starttls for POP3', function () {
    $driver = new Stream('stream');
    $answer = $driver->show(POP_HOST, '110', [Stream::OPTION_CHAIN => false]);
    expect($answer[0][0]['subject']['CN'])->toEqual(POP_IMAP_CN);
})->group('network');

it('can starttls for IMAP', function () {
    $driver = new Stream('stream');
    $answer = $driver->show(IMAP_HOST, '143', [Stream::OPTION_CHAIN => false]);
    expect($answer[0][0]['subject']['CN'])->toEqual(POP_IMAP_CN);
})->group('network');

it('can starttls for SMTP', function () {
    $driver = new Stream('stream');
    $answer = $driver->show(SMTP_HOST, '587', [Stream::OPTION_CHAIN => false]);
    expect($answer[0][0]['subject']['CN'])->toEqual(SMTP_HOST);
})->group('network');
