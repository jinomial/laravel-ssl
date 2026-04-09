<?php

namespace Jinomial\LaravelSsl\Drivers;

use Jinomial\LaravelSsl\Contracts\Ssl\Driver as DriverContract;
use Jinomial\LaravelSsl\Support\Certificate;
use Jinomial\LaravelSsl\Support\CertificateCollection;
use OpenSSLCertificate;
use UnexpectedValueException;

final class Stream extends Driver implements DriverContract
{
    /**
     * The name of the option for specifying retrieval of the full chain.
     */
    public const OPTION_CHAIN = 'peer_certificate_chain';
    public const OPTION_CHAIN_DEFAULT = true;

    /**
     * The name of the option for specifying the crypto method.
     */
    public const OPTION_CRYPTO_METHOD = 'crypto_method';
    public const OPTION_CRYPTO_METHOD_DEFAULT = STREAM_CRYPTO_METHOD_TLS_CLIENT;

    /**
     * Create a new Ssl driver instance.
     *
     * @return void
     */
    public function __construct(protected string $name, protected array $config = [])
    {
        //
    }

    /**
     * Get a security certificate used by $host:$port.
     */
    #[\Override]
    public function show(string|array $host, string $port = '443', array $options = []): CertificateCollection
    {
        if (! is_array($host)) {
            $host = [['host' => $host, 'port' => $port]];
        }

        return $this->runStreams($host, $options);
    }

    private function runStreams(array $questions, array $options): CertificateCollection
    {
        $results = new CertificateCollection();

        foreach ($questions as $question) {
            try {
                $results = $results->merge($this->capture($question, $options));
            } catch (UnexpectedValueException $e) {
                // Connection error. Port closed or not SSL/TLS/STARTTLS.
            }
        }

        return $results;
    }

    private function capture(array $question, array $options): CertificateCollection
    {
        $host = $question['host'] ?? '';
        $port = (string)($question['port'] ?? '443');

        // Use StartTLS for unsecure ports.
        $starttls = false;
        $type = 'tls';
        if ($port === '25' || $port === '587') {
            $starttls = true;
            $type = 'smtp';
        } elseif ($port === '143') {
            $starttls = true;
            $type = 'imap';
        } elseif ($port === '110') {
            $starttls = true;
            $type = 'pop';
        }

        $stream = $this->openStream(
            $starttls ? 'tcp' : 'tls',
            $host,
            $port,
            $options,
        );

        if ($starttls) {
            // Upgrade the connection according to the protocol
            switch ($type) {
                case 'imap':
                    $this->startTlsImap($stream);

                    break;
                case 'pop':
                    $this->startTlsPop($stream);

                    break;
                case 'smtp':
                    $this->startTlsSmtp($stream);

                    break;
                default:
                    throw new UnexpectedValueException(
                        "STARTTLS failed. Unknown protocol '$type'."
                    );
            }
        }

        $params = stream_context_get_params($stream);
        $captured = $params['options']['ssl']['peer_certificate_chain'] ??
            [$params['options']['ssl']['peer_certificate']];

        $chain = new CertificateCollection();
        foreach ($captured as $certificate) {
            $chain->push(new Certificate($this->parseX509($certificate), null, $host, $port));
        }

        return $chain;
    }

    /**
     * @return resource
     */
    private function openStream(string $scheme, string $host, string $port, array $options = [])
    {
        $timeout10s = 10;
        $timeout = $this->config['timeout'] ?? $timeout10s;
        $stream = @stream_socket_client(
            "$scheme://$host:$port",
            $errorNumber,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $this->getStreamContext($options),
        );

        if (! $stream) {
            throw new UnexpectedValueException(
                "Error $errorNumber: $errorMessage"
            );
        }

        return $stream;
    }

    /**
     * Returns an SSL transport context.
     *
     * Specifies the crypto method to use and to capture and verify peer
     * certificates.
     *
     * @return resource
     */
    private function getStreamContext(array $options = [])
    {
        $chain = ($options[Stream::OPTION_CHAIN] ?? Stream::OPTION_CHAIN_DEFAULT) === true;
        $capture = $chain ? 'capture_peer_cert_chain' : 'capture_peer_cert';

        $cryptoMethod = $options[Stream::OPTION_CRYPTO_METHOD] ??
            Stream::OPTION_CRYPTO_METHOD_DEFAULT;

        $context = stream_context_create([
            'ssl' => [
                $capture => true,
                'crypto_method' => $cryptoMethod,
            ],
        ]);

        return $context;
    }

    /**
     * @return void
     *
     * @param resource $stream
     */
    private function startTlsSmtp($stream)
    {
        if ($this->readLineForSuccess($stream, '220 ')) {

            // The server is connected...

            $hostname = gethostname() ?: 'localhost';
            $heloFqdn = gethostbyaddr(gethostbyname($hostname));
            fwrite($stream, "HELO $heloFqdn\n");

            if ($this->readLineForSuccess($stream, '250 ')) {

                // The server is ready...

                fwrite($stream, "STARTTLS\n");

                if ($this->readLineForSuccess($stream, '220 ')) {

                    // The server supports STARTTLS...

                    $enabled = stream_socket_enable_crypto($stream, true, null);

                    if ($enabled !== true) {
                        throw new UnexpectedValueException(
                            'STARTTLS failed. This socket may not have TLS capability.'
                        );
                    }

                    // Be nice and let server know we are done.
                    fwrite($stream, "QUIT\n");
                }
            }
        }
    }

    /**
     * @return void
     *
     * @param resource $stream
     */
    private function startTlsPop($stream)
    {
        if ($this->readLineForSuccess($stream, '+OK')) {

            // The server is ready...
            fwrite($stream, "STLS\n");

            if ($this->readLineForSuccess($stream, '+OK ')) {

                // The server supports STARTTLS...

                $enabled = stream_socket_enable_crypto($stream, true, null);

                if ($enabled !== true) {
                    throw new UnexpectedValueException(
                        'STARTTLS failed. This socket may not have TLS capability.'
                    );
                }

                // Be nice and let server know we are done.
                fwrite($stream, "QUIT\n");
            }
        }
    }

    /**
     * @return void
     *
     * @param resource $stream
     */
    private function startTlsImap($stream)
    {
        if ($this->readLineForSuccess($stream, '* OK ')) {

            // The server is ready...
            fwrite($stream, ". STARTTLS\n");

            if ($this->readLineForSuccess($stream, '. OK ')) {

                // The server supports STARTTLS...

                $enabled = stream_socket_enable_crypto($stream, true, null);

                if ($enabled !== true) {
                    throw new UnexpectedValueException(
                        'STARTTLS failed. This socket may not have TLS capability.'
                    );
                }

                // Be nice and let the server know we are done.
                fwrite($stream, ". LOGOUT\n");
            }
        }
    }

    private function parseX509(OpenSSLCertificate $raw): array
    {
        $parsed = openssl_x509_parse($raw, true);

        if ($parsed === false) {
            throw new UnexpectedValueException(
                'Invalid x509 certificate. Unable to parse.'
            );
        }

        return $parsed;
    }

    /**
     * @return false|string
     */
    /**
     * @param resource $stream
     */
    private function readLine($stream): string|false
    {
        return stream_get_line($stream, 1000, "\n");
    }

    /**
     * @param resource $stream
     */
    private function readLineForSuccess($stream, string $successNeedle): bool
    {
        $line = $this->readLine($stream);

        if ($line === false) {
            return false;
        }

        return strpos($line, $successNeedle) === 0;
    }
}
