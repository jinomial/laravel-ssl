<?php

namespace Jinomial\LaravelSsl\Contracts\Ssl;

use Jinomial\LaravelSsl\Support\CertificateCollection;

interface Driver
{
    /**
     * Perform a security certificate lookup.
     *
     * @api
     */
    public function show(string|array $host, string $port = '443', array $options = []): CertificateCollection;
}
