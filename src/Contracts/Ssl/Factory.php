<?php

namespace Jinomial\LaravelSsl\Contracts\Ssl;

interface Factory
{
    /**
     * Get an SSL driver instance by name.
     */
    public function driver(?string $name = null): Driver;
}
