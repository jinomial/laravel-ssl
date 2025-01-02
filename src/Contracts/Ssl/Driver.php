<?php

namespace Jinomial\LaravelSsl\Contracts\Ssl;

interface Driver
{
    /**
     * Perform a security certificate lookup.
     *
     * @api
     */
    public function show(string $host, string $port = '443', array $options = []);
}
