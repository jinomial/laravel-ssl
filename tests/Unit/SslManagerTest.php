<?php

namespace Jinomial\LaravelSsl\Tests\Unit;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Jinomial\LaravelSsl\Drivers\Driver;
use Jinomial\LaravelSsl\Drivers\OpenSsl;
use Jinomial\LaravelSsl\SslManager;
use Mockery;

uses()->group('manager');

test('SslManager constructs', function () {
    $app = Application::configure()->create();
    $manager = new SslManager($app);
    expect($manager)->toBeInstanceOf(SslManager::class);
});

test('SslManager can get application', function () {
    $app = Application::configure()->create();
    $manager = new SslManager($app);
    expect($manager->getApplication())->toEqual($app);
});

test('SslManager application can be set', function () {
    $app = Config::getFacadeApplication();
    $newApp = Application::configure()->create();
    $manager = new SslManager($app);
    $manager->setApplication($newApp);
    expect($manager->getApplication())->toEqual($newApp);
});

test('SslManager can get default driver name', function () {
    $name = uniqid();
    $config = Config::set(['ssl' => ['default' => $name]]);
    $manager = new SslManager(Config::getFacadeApplication());
    expect($manager->getDefaultDriver())->toEqual($name);
});

test('SslManager can set default driver name', function () {
    $name = uniqid();
    $config = Config::set(['ssl' => ['default' => 'driver']]);
    $manager = new SslManager(Config::getFacadeApplication());
    $manager->setDefaultDriver($name);
    expect($manager->getDefaultDriver())->toEqual($name);
});

test('SslManager gets a driver by name', function () {
    // openssl is a real driver that is created by the function
    // SslManager::createOpensslDriver()
    $driverConfig = ['driver' => 'openssl'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'mydriver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());
    $driver = $manager->driver('mydriver');
    expect($driver)->toBeInstanceOf(Driver::class);
});

test('SslManager supports openssl driver', function () {
    $driverConfig = ['driver' => 'openssl'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'mydriver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());
    $driver = $manager->driver('mydriver');
    expect($driver)->toBeInstanceOf(OpenSsl::class);
});

test('SslManager gets a default driver when not specified', function () {
    // openssl is a real driver that is created by the function
    // SslManager::createOpensslDriver()
    $driverConfig = ['driver' => 'openssl'];
    $config = Config::set(['ssl' => [
        'default' => 'my-default-driver',
        'drivers' => [
            'my-default-driver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());
    $driver = $manager->driver();
    expect($driver)->toBeInstanceOf(Driver::class);
});

test('SslManager throws error when driver not configured', function () {
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());
    $driver = $manager->driver('unconfigured-driver');
})->throws(InvalidArgumentException::class);

test('SslManager throws error when a driver is not supported', function () {
    // fake is a fake driver that can't be created because the function
    // SslManager::createFakeDriver() doesn't exist.
    $driverConfig = ['driver' => 'fake'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());
    $driver = $manager->driver('defined-driver');
})->throws(InvalidArgumentException::class);

test('SslManager can be extended with custom driver creators', function () {
    // custom is a driver that can't be created because the function
    // SslManager::createCustomDriver() doesn't exist.
    $driverConfig = ['driver' => 'custom'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());
    // Extend the manager with a driver called 'custom'.
    $manager->extend(
        'custom',
        fn ($theApp, $theName, $theConfig) => \Mockery::spy(Driver::class)
    );
    $driver = $manager->driver('defined-driver');
    expect($driver)->toBeInstanceOf(Driver::class);
});

test('SslManager custom creators overwrite existing creators', function () {
    // openssl is a real driver that can be created because the function
    // SslManager::createOpensslDriver() exists.
    $driverConfig = ['driver' => 'openssl'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());
    // Extend the manager to overwrite the openssl driver.
    $overwrittenDriver = Mockery::mock(Driver::class);
    $manager->extend(
        'openssl',
        fn ($theApp, $theName, $theConfig) => $overwrittenDriver
    );
    $driver = $manager->driver('defined-driver');
    expect($driver)->toEqual($overwrittenDriver);
});

test('SslManager caches drivers locally by name', function () {
    // openssl is a real driver that can be created because the function
    // SslManager::createOpensslDriver() exists.
    $driverConfig = ['driver' => 'openssl'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());

    // Get the driver so now it is cached.
    $driver = $manager->driver('defined-driver');

    // Extend the manager to overwrite the openssl driver.
    $overwrittenDriver = Mockery::mock(Driver::class);
    $manager->extend(
        'openssl',
        fn ($theApp, $theName, $theConfig) => $overwrittenDriver
    );

    // Get the driver a second time and it should be the original and not
    // the extended one.
    expect($manager->driver('defined-driver'))->toBeInstanceOf(Driver::class);
});

test('SslManager can purge cached drivers', function () {
    // openssl is a real driver that can be created because the function
    // SslManager::createOpensslDriver() exists.
    $driverConfig = ['driver' => 'openssl'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());

    // Get the openssl driver so now it is cached.
    $driver = $manager->driver('defined-driver');

    // Extend the manager to overwrite the openssl driver.
    $overwrittenDriver = Mockery::mock(Driver::class);
    $manager->extend(
        'openssl',
        fn ($theApp, $theName, $theConfig) => $overwrittenDriver
    );

    // Purge the cache so the original driver should be gone
    // and getting the driver again should return the extended one.
    $manager->purge('defined-driver');

    // Get the driver a second time and it should be the original and not
    // the extended one.
    expect($manager->driver('defined-driver'))->toEqual($overwrittenDriver);
});

test('SslManager purges by name and not driver', function () {
    // openssl is a real driver that can be created because the function
    // SslManager::createOpensslDriver() exists.
    $driverConfig = ['driver' => 'openssl'];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());

    // Get the driver so now it is cached.
    $driver = $manager->driver('defined-driver');

    // Extend the manager to overwrite the openssl driver creator.
    $overwrittenDriver = uniqid();
    $manager->extend(
        'openssl',
        fn ($theApp, $theName, $theConfig) => $overwrittenDriver
    );

    // Try to purge the cache by driver instead of driver name.
    // The original driver should still be there and getting the driver
    // again should return the cached one.
    $manager->purge('openssl');

    // Get the driver a second time and it should be the original and not
    // the extended one.
    expect($manager->driver('defined-driver'))->toBeInstanceOf(Driver::class);
});

test('SslManager can forget all drivers', function () {
    $driver1 = uniqid();
    $driver2 = uniqid();
    $driverConfig1 = ['driver' => $driver1];
    $driverConfig2 = ['driver' => $driver2];
    $config = Config::set(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver-1' => $driverConfig1,
            'defined-driver-2' => $driverConfig2,
        ],
    ]]);
    $manager = new SslManager(Config::getFacadeApplication());

    // Extend the manager to support the custom drivers.
    $manager->extend(
        $driver1,
        fn ($theApp, $theName, $theConfig) => Mockery::mock(Driver::class)
    );
    $manager->extend(
        $driver2,
        fn ($theApp, $theName, $theConfig) => Mockery::mock(Driver::class)
    );

    // Get both drivers so now both are cached.
    $manager->driver('defined-driver-1');
    $manager->driver('defined-driver-2');

    // Extend the manager to overwrite the driver creators.
    $newDriver1 = Mockery::mock(Driver::class);
    $manager->extend(
        $driver1,
        fn ($theApp, $theName, $theConfig) => $newDriver1
    );
    $newDriver2 = Mockery::mock(Driver::class);
    $manager->extend(
        $driver2,
        fn ($theApp, $theName, $theConfig) => $newDriver2
    );

    // Forget all drivers so getting the drivers again should use the new
    // creators instead of getting the drivers from the cache.
    $manager->forgetDrivers();

    // Get the drivers a second time and it should be the new drivers.
    expect($manager->driver('defined-driver-1'))->toEqual($newDriver1)
        ->and($manager->driver('defined-driver-2'))->toEqual($newDriver2);
});
