<?php

namespace Jinomial\LaravelSsl\Tests\Integration\Commands;

use Illuminate\Support\Facades\Artisan;

const HOST = 'jinomial.com';

uses()->group('commands');

test('Artisan command can show ' . HOST, function () {
    // Commands that fail will exit with an exception/non-zero value.
    $exitCode = Artisan::call('ssl:show', [
        'host' => HOST,
        'port' => 443,
    ]);
    expect($exitCode)->toEqual(0);
})->group('network');
