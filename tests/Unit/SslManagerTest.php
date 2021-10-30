<?php

namespace Jinomial\LaravelSsl\Tests\Unit;

use Illuminate\Config\Repository;
use InvalidArgumentException;
use Jinomial\LaravelSsl\Drivers\Driver;
use Jinomial\LaravelSsl\Drivers\OpenSsl;
use Jinomial\LaravelSsl\SslManager;

uses()->group('manager');

test('SslManager constructs', function () {
    $app = [];
    $manager = new SslManager($app);
    expect($manager)->toBeInstanceOf(SslManager::class);
});

test('SslManager can get application', function () {
    $app = [uniqid()];
    $manager = new SslManager($app);
    expect($manager->getApplication())->toEqual($app);
});

test('SslManager application can be set', function () {
    $app = [];
    $newApp = [uniqid()];
    $manager = new SslManager($app);
    $manager->setApplication($newApp);
    expect($manager->getApplication())->toEqual($newApp);
});

test('SslManager can get default driver name', function () {
    $name = uniqid();
    $config = new Repository(['ssl' => ['default' => $name]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    expect($manager->getDefaultDriver())->toEqual($name);
});

test('SslManager can set default driver name', function () {
    $name = uniqid();
    $config = new Repository(['ssl' => ['default' => 'driver']]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    $manager->setDefaultDriver($name);
    expect($manager->getDefaultDriver())->toEqual($name);
});

test('SslManager gets a driver by name', function () {
    // openssl is a real driver that is created by the function
    // SslManager::createOpensslDriver()
    $driverConfig = ['driver' => 'openssl'];
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'mydriver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    $driver = $manager->driver('mydriver');
    expect($driver)->toBeInstanceOf(Driver::class);
});

test('SslManager supports openssl driver', function () {
    $driverConfig = ['driver' => 'openssl'];
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'mydriver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    $driver = $manager->driver('mydriver');
    expect($driver)->toBeInstanceOf(OpenSsl::class);
});

test('SslManager gets a default driver when not specified', function () {
    // openssl is a real driver that is created by the function
    // SslManager::createOpensslDriver()
    $driverConfig = ['driver' => 'openssl'];
    $config = new Repository(['ssl' => [
        'default' => 'my-default-driver',
        'drivers' => [
            'my-default-driver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    $driver = $manager->driver();
    expect($driver)->toBeInstanceOf(Driver::class);
});

test('SslManager throws error when driver not configured', function () {
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    $driver = $manager->driver('unconfigured-driver');
})->throws(InvalidArgumentException::class);

test('SslManager throws error when a driver is not supported', function () {
    // fake is a fake driver that can't be created because the function
    // SslManager::createFakeDriver() doesn't exist.
    $driverConfig = ['driver' => 'fake'];
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    $driver = $manager->driver('defined-driver');
})->throws(InvalidArgumentException::class);

test('SslManager can be extended with custom driver creators', function () {
    // custom is a driver that can't be created because the function
    // SslManager::createCustomDriver() doesn't exist.
    $driverConfig = ['driver' => 'custom'];
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
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
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);
    // Extend the manager to overwrite the openssl driver.
    $overwrittenDriver = 'overwritten-openssl-driver';
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
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);

    // Get the driver so now it is cached.
    $driver = $manager->driver('defined-driver');

    // Extend the manager to overwrite the openssl driver.
    $overwrittenDriver = uniqid();
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
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);

    // Get the openssl driver so now it is cached.
    $driver = $manager->driver('defined-driver');

    // Extend the manager to overwrite the openssl driver.
    $overwrittenDriver = uniqid();
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
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver' => $driverConfig,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);

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
    $config = new Repository(['ssl' => [
        'default' => 'no-default-driver',
        'drivers' => [
            'defined-driver-1' => $driverConfig1,
            'defined-driver-2' => $driverConfig2,
        ],
    ]]);
    $app = ['config' => $config];
    $manager = new SslManager($app);

    // Extend the manager to support the custom drivers.
    $manager->extend(
        $driver1,
        fn ($theApp, $theName, $theConfig) => $driver1
    );
    $manager->extend(
        $driver2,
        fn ($theApp, $theName, $theConfig) => $driver2
    );

    // Get both drivers so now both are cached.
    $manager->driver('defined-driver-1');
    $manager->driver('defined-driver-2');

    // Extend the manager to overwrite the driver creators.
    $newDriver1 = uniqid();
    $manager->extend(
        $driver1,
        fn ($theApp, $theName, $theConfig) => $newDriver1
    );
    $newDriver2 = uniqid();
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
