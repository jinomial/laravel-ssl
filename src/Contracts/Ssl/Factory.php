<?php

namespace Jinomial\LaravelSsl\Contracts\Ssl;

interface Factory
{
    /**
     * Get an SSL driver instance by name.
     *
     * @param  string|null  $name
     * @return \Jinomial\LaravelSsl\Contracts\Ssl\Driver
     */
    public function driver($name = null);
}
