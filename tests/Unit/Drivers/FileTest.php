<?php

namespace Jinomial\LaravelSsl\Tests\Unit\Drivers;

use Jinomial\LaravelSsl\Drivers\File;
use Jinomial\LaravelSsl\Support\Certificate;

uses()->group('drivers', 'file');

it('can parse a local certificate file', function () {
    $driver = new File('file');
    $path = __DIR__ . '/test.crt';

    $results = $driver->show($path);

    expect($results)->toHaveCount(1);
    expect($results->first())->toBeInstanceOf(Certificate::class);
    expect($results->first()->getCommonName())->toBe('test.local');
    expect($results->first()->host)->toBe($path);
    expect($results->first()->port)->toBe('file');
});

it('skips non-existent files', function () {
    $driver = new File('file');
    $results = $driver->show('non-existent-file.crt');

    expect($results)->toBeEmpty();
});

it('can parse multiple files', function () {
    $driver = new File('file');
    $path = __DIR__ . '/test.crt';

    $results = $driver->show([$path, $path]);

    expect($results)->toHaveCount(2);
});
