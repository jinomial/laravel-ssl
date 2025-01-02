<?php

namespace Jinomial\LaravelSsl;

use Closure;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Jinomial\LaravelSsl\Contracts\Ssl\Factory as FactoryContract;
use Jinomial\LaravelSsl\Drivers\Driver;

class SslManager implements FactoryContract
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved drivers.
     */
    protected array $drivers = [];

    /**
     * The registered custom driver creators.
     */
    protected array $customCreators = [];

    /**
     * Create a new Ssl manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a driver instance by name.
     */
    public function driver(?string $name = null): Driver
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Attempt to get the Ssl driver from the local cache.
     */
    protected function get(string $name): Driver
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given Ssl driver.
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): Driver
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Ssl driver [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        } else {
            $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

            if (method_exists($this, $driverMethod)) {
                return $this->{$driverMethod}($name, $config);
            } else {
                throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
            }
        }
    }

    /**
     * Call a custom driver creator.
     *
     * @return mixed
     */
    protected function callCustomCreator(string $name, array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Create an instance of the OpenSsl driver.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function createOpensslDriver(string $name, array $config): Driver
    {
        return new Drivers\OpenSsl(
            $name,
            $this->guzzle($config)
        );
    }

    /**
     * Get a fresh Guzzle HTTP client instance.
     */
    protected function guzzle(array $config): HttpClient
    {
        return new HttpClient(Arr::add(
            $config['guzzle'] ?? [],
            'connect_timeout',
            60
        ));
    }

    /**
     * Get the Ssl driver configuration.
     */
    protected function getConfig(string $name): ?array
    {
        return Config::get("ssl.drivers.{$name}");
    }

    /**
     * Get the default SSL driver name.
     */
    public function getDefaultDriver(): string
    {
        return Config::get('ssl.default');
    }

    /**
     * Set the default driver name.
     *
     * @api
     */
    public function setDefaultDriver(string $name): void
    {
        Config::set('ssl.default', $name);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @api
     */
    public function extend(string $driver, Closure $callback): SslManager
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Destroy the given driver instance and remove from local cache.
     *
     * @api
     */
    public function purge(?string $name = null): void
    {
        $name = $name ?: $this->getDefaultDriver();

        unset($this->drivers[$name]);
    }

    /**
     * Get the application instance used by the manager.
     *
     * @api
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @api
     */
    public function setApplication(Application $app): SslManager
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Forget all of the resolved driver instances.
     *
     * @api
     */
    public function forgetDrivers(): SslManager
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @api
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
