<?php

namespace Jinomial\LaravelSsl\Drivers;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use InvalidArgumentException;
use Jinomial\LaravelSsl\Contracts\Ssl\Driver as DriverContract;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class OpenSsl extends Driver implements DriverContract
{
    /**
     * The name of the option for specifying retrieval of an id-ad-caIssuers.
     *
     * @see rfc5280#section-4.2.2.1
     */
    public const OPTION_ID_AD_CAISSUERS = 'id-ad-caIssuers';
    public const OPTION_ID_AD_CAISSUERS_DEFAULT = false;

    /**
     * The name of the option for specifying inform is DER.
     *
     * The presence of this option enables it. It has no value.
     */
    public const OPTION_DER = 'der';

    /**
     * Guzzle client instance.
     */
    protected ClientInterface $client;

    /**
     * Create a new Ssl driver instance.
     *
     * @return void
     */
    public function __construct(string $name, ClientInterface $client)
    {
        $this->name = $name;
        $this->client = $client;
    }

    /**
     * Get a security certificate used by $host:$port.
     */
    public function show(string|array $host, string $port = '443', array $options = []): array
    {
        if (! is_array($host)) {
            $host = [['host' => $host, 'port' => $port]];
        }

        return $this->runProcesses($host, $options);
    }

    /**
     * Make HTTP requests for each lookup question.
     */
    protected function runProcesses(array $questions, array $options): array
    {
        $adCaIssuers = $options[OpenSsl::OPTION_ID_AD_CAISSUERS] ??
            OpenSsl::OPTION_ID_AD_CAISSUERS_DEFAULT;
        $results = [];

        foreach ($questions as $question) {
            $host = $question['host'] ?? 'localhost';
            $port = (string)($question['port'] ?? '443');

            $certificate = null;
            $verification = null;

            try {
                if ($adCaIssuers) {
                    $x509 = $this->getCaIssuers($host);
                    $verification = null;
                } else {
                    $details = $this->getCertificate($host, $port, $options);
                    $x509 = $details['x509'];
                    $verification = $details['verification'];
                }
                $certificate = openssl_x509_parse($x509);
            } catch (ServerException $e) {
            } catch (ProcessFailedException $e) {
            } catch (ProcessTimedOutException $e) {
            }

            $results[] = [
                'certificate' => $certificate,
                'verification' => $verification,
            ];
        }

        return $results;
    }

    /**
     * Get the parsed certificate returned from a socket.
     */
    protected function getCertificate(string $host, string $port, array $options = []): array
    {
        if (! $host) {
            throw new InvalidArgumentException(
                'Cannot connect to null host.'
            );
        }

        $handshake = $this->getHandshake($host, $port, $options);
        $verification = $this->getVerification($handshake);
        $x509 = $this->getX509($handshake, $options);

        return [
            'x509' => $x509,
            'verification' => $verification,
        ];
    }

    /**
     * Get the output of `echo | openssl s_client -connect $host:$port`.
     *
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function getHandshake(string $host, string $port, array $options = []): string
    {
        $arguments = [
            'openssl',
            's_client',
            '-connect',
            "{$host}:{$port}",
            '-servername',
            "{$host}",
            '-verify',
            '10',
        ];
        // Use StartTLS for unsecure ports.
        if ($port === '25' || $port === '587') {
            $arguments[] = '-starttls';
            $arguments[] = 'smtp';
        } elseif ($port === '143') {
            $arguments[] = '-starttls';
            $arguments[] = 'imap';
        } elseif ($port === '110') {
            $arguments[] = '-starttls';
            $arguments[] = 'pop3';
        }

        // Capture the handshake.
        $newlineStream = new InputStream();
        $newlineStream->write("\n");
        $handshakeProcess = new Process($arguments);
        $handshakeProcess->setTimeout(10);
        $handshakeProcess->setInput($newlineStream);
        $handshakeProcess->start();
        $newlineStream->close();
        $handshakeProcess->wait();
        // executes after the command finishes
        if (! $handshakeProcess->isSuccessful()) {
            throw new ProcessFailedException($handshakeProcess);
        }
        $handshake = $handshakeProcess->getOutput();

        return $handshake;
    }

    /**
     * Convert certificate data into x509 format.
     */
    protected function getX509(string $input, array $options = []): string
    {
        $arguments = [
            'openssl',
            'x509',
            '-text',
        ];
        if (array_key_exists(OpenSsl::OPTION_DER, $options)) {
            $arguments[] = '-inform';
            $arguments[] = 'DER';
        }
        $inputStream = new InputStream();
        $inputStream->write($input);
        $x509Process = new Process($arguments);
        $x509Process->setTimeout(10);
        $x509Process->setInput($inputStream);
        $x509Process->start();
        $inputStream->close();
        $x509Process->wait();
        // executes after the command finishes
        if (! $x509Process->isSuccessful()) {
            throw new ProcessFailedException($x509Process);
        }
        $x509 = $x509Process->getOutput();

        return $x509;
    }

    /**
     * Get an id-ad-caIssuers as an x509.
     */
    protected function getCaIssuers(string $url): string
    {
        $response = $this->client->request('GET', $url, [
            // 'debug' => TRUE,
            'http_errors' => true,
        ]);

        $der = (string) $response->getBody();
        $x509 = $this->getX509($der, [
            OpenSsl::OPTION_DER => true,
        ]);

        return $x509;
    }

    /**
     * Parse the "Verify return code: xx (message)" into code and message.
     */
    private function getVerification(string $handshake): ?array
    {
        $lines = explode("\n", $handshake);
        $needle = 'verify return code';
        $pattern = '/([0-9]{1,2})\s\((.*)\)/';
        $matches = [];
        foreach ($lines as $l) {
            $line = strtolower(trim($l));
            if (str_starts_with($line, $needle)) {
                preg_match($pattern, $line, $matches);
                if (count($matches) === 3) {
                    return [
                        'code' => $matches[1],
                        'message' => $matches[2],
                    ];
                }
            }
        }

        return null;
    }
}
