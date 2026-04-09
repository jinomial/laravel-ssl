<?php

namespace Jinomial\LaravelSsl\Tests\Integration\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Jinomial\LaravelSsl\Facades\Ssl;
use Jinomial\LaravelSsl\Support\Certificate;
use Jinomial\LaravelSsl\Support\CertificateCollection;

uses()->group('commands');

it('warns if no hosts are provided or configured', function () {
    Config::set('ssl.monitored_hosts', []);

    $this->artisan('ssl:check')
        ->expectsOutput('No hosts provided or configured in ssl.monitored_hosts.')
        ->assertExitCode(1);
});

it('checks hosts provided as arguments', function () {
    // Mock the Facade to return a predictable certificate
    $validTo = Carbon::now()->addDays(10)->timestamp;
    $cert = new Certificate([
        'subject' => ['CN' => 'test.com'],
        'validTo_time_t' => $validTo,
    ], null, 'test.com', '443');

    Ssl::shouldReceive('show')->with('test.com')->andReturn(new CertificateCollection([$cert]));

    $this->artisan('ssl:check', ['hosts' => ['test.com']])
        ->expectsOutput('Checking SSL certificates...')
        ->assertExitCode(0);
});

it('checks hosts from configuration', function () {
    Config::set('ssl.monitored_hosts', ['example.com']);

    $validTo = Carbon::now()->addDays(10)->timestamp;
    $cert = new Certificate([
        'subject' => ['CN' => 'example.com'],
        'validTo_time_t' => $validTo,
    ], null, 'example.com', '443');

    Ssl::shouldReceive('show')->with('example.com')->andReturn(new CertificateCollection([$cert]));

    $this->artisan('ssl:check')
        ->expectsOutput('Checking SSL certificates...')
        ->assertExitCode(0);
});
