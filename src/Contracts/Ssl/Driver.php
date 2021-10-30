<?php

namespace Jinomial\LaravelSsl\Contracts\Ssl;

interface Driver
{
    /**
     * Perform a security certificate lookup.
     */
    public function show($host, $port = '443', array $options = []);
}
