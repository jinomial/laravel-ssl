<?php

namespace Jinomial\LaravelSsl\Drivers;

use Jinomial\LaravelSsl\Contracts\Ssl\Driver as DriverContract;
use Jinomial\LaravelSsl\Support\Certificate;
use Jinomial\LaravelSsl\Support\CertificateCollection;

/**
 * @psalm-api
 */
final class File extends Driver implements DriverContract
{
    public function __construct(protected string $name)
    {
    }

    /**
     * Get a security certificate from a local file.
     *
     * @param string|array $host The path(s) to the certificate file(s).
     * @param string $port Unused for this driver.
     * @param array $options Unused for this driver.
     */
    #[\Override]
    public function show(string|array $host, string $port = '443', array $options = []): CertificateCollection
    {
        $files = is_array($host) ? $host : [['host' => $host]];
        $results = new CertificateCollection();

        foreach ($files as $fileData) {
            $path = is_array($fileData) ? ($fileData['host'] ?? '') : $fileData;

            if (! is_string($path) || ! file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            $parsed = openssl_x509_parse($content);

            if ($parsed !== false) {
                $results->push(new Certificate($parsed, null, $path, 'file'));
            }
        }

        return $results;
    }
}
