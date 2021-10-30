<?php

namespace Jinomial\LaravelSsl\Drivers;

use Jinomial\LaravelSsl\Contracts\Ssl\Driver as DriverContract;

abstract class Driver implements DriverContract
{
    /**
     * The name that is configured for the driver.
     *
     * @var string
     */
    protected $name;
}
