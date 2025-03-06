# Laravel SSL

An SSL/TLS service and facade for Laravel. Capture peer certificates using 'openssl' or 'stream' drivers, or create your own driver.

| Driver  | Description                    |
|---------|--------------------------------|
| openssl | Executes the *openssl* command |
| stream  | PHP's `stream_*()` functions   |

## Installation

Install the package via composer:

```bash
composer require jinomial/laravel-ssl
```

The default configuration is to use the *openssl* driver.

```php
'openssl' => [
    'driver' => 'openssl',
],
```

Publish the config file to change the default driver:
```bash
php artisan vendor:publish --provider="Jinomial\LaravelSsl\SslServiceProvider" --tag="laravel-ssl-config"
```

## Usage

Capture the certificate of *jinomial.com:443* using the default driver:

```php
$response = Ssl::show('jinomial.com', 443);
print_r($response);

// > Response varies by driver
```

Use a specific driver:

```php
$response = Ssl::driver('stream')->show('jinomial.com', '443', [
    // Get the certificate only without the CA Chain.
    Jinomial\LaravelSsl\Drivers\Stream::OPTION_CHAIN => false,
]);
```

Multiple lookups at once:

```php
$response = Ssl::show([
    ['host' => 'jinomial.com', 'port' => '443'],
    ['host' => 'www2.jinomial.com', 'port' => '8443'],
]);
```

### openssl driver responses

The *openssl* driver executes the *openssl* binary (must be in your $PATH) to retrieve the X509 certificate.

Each response is indexed.

`certificate` is the return value from [`openssl_x509_parse()`](https://www.php.net/manual/function.openssl-x509-parse.php).

`verification` contains the verification code and verification message.

```
Array
(
    [0] => Array
        (
            [certificate] => Array
                (
                    [name] => /C=US/ST=California/L=San Francisco/O=Cloudflare, Inc./CN=sni.cloudflaressl.com
                    [subject] => Array
                        (
                            [C] => US
                            [ST] => California
                            [L] => San Francisco
                            [O] => Cloudflare, Inc.
                            [CN] => sni.cloudflaressl.com
                        )

                    [hash] => c959965e
                    [issuer] => Array
                        (
                            [C] => US
                            [O] => Cloudflare, Inc.
                            [CN] => Cloudflare Inc ECC CA-3
                        )

                    [version] => 2
                    [serialNumber] => 7490133585878873344260773043003356172
                    [serialNumberHex] => 05A28C18F8F74ACBCCF6A4542736740C
                    [validFrom] => 211004000000Z
                    [validTo] => 221003235959Z
                    [validFrom_time_t] => 1633305600
                    [validTo_time_t] => 1664841599
                    [signatureTypeSN] => ecdsa-with-SHA256
                    [signatureTypeLN] => ecdsa-with-SHA256
                    [signatureTypeNID] => 794
                    [purposes] => Array
                        (
                            [1] => Array
                                (
                                    [0] => 1
                                    [1] =>
                                    [2] => sslclient
                                )

                            [2] => Array
                                (
                                    [0] => 1
                                    [1] =>
                                    [2] => sslserver
                                )

                            [3] => Array
                                (
                                    [0] =>
                                    [1] =>
                                    [2] => nssslserver
                                )

                            [4] => Array
                                (
                                    [0] =>
                                    [1] =>
                                    [2] => smimesign
                                )

                            [5] => Array
                                (
                                    [0] =>
                                    [1] =>
                                    [2] => smimeencrypt
                                )

                            [6] => Array
                                (
                                    [0] =>
                                    [1] =>
                                    [2] => crlsign
                                )

                            [7] => Array
                                (
                                    [0] => 1
                                    [1] => 1
                                    [2] => any
                                )

                            [8] => Array
                                (
                                    [0] => 1
                                    [1] =>
                                    [2] => ocsphelper
                                )

                            [9] => Array
                                (
                                    [0] =>
                                    [1] =>
                                    [2] => timestampsign
                                )

                        )

                    [extensions] => Array
                        (
                            [authorityKeyIdentifier] => keyid:A5:CE:37:EA:EB:B0:75:0E:94:67:88:B4:45:FA:D9:24:10:87:96:1F

                            [subjectKeyIdentifier] => 1B:20:D1:CD:00:32:24:77:9F:F8:22:94:0F:B6:48:7F:39:B1:BE:C8
                            [subjectAltName] => DNS:*.jinomial.com, DNS:sni.cloudflaressl.com, DNS:jinomial.com
                            [keyUsage] => Digital Signature
                            [extendedKeyUsage] => TLS Web Server Authentication, TLS Web Client Authentication
                            [crlDistributionPoints] =>
Full Name:
  URI:http://crl3.digicert.com/CloudflareIncECCCA-3.crl

Full Name:
  URI:http://crl4.digicert.com/CloudflareIncECCCA-3.crl

                            [certificatePolicies] => Policy: 2.23.140.1.2.2
  CPS: http://www.digicert.com/CPS

                            [authorityInfoAccess] => OCSP - URI:http://ocsp.digicert.com
CA Issuers - URI:http://cacerts.digicert.com/CloudflareIncECCCA-3.crt

                            [basicConstraints] => CA:FALSE
                            [ct_precert_scts] => Signed Certificate Timestamp:
    Version   : v1 (0x0)
    Log ID    : 46:A5:55:EB:75:FA:91:20:30:B5:A2:89:69:F4:F3:7D:
                11:2C:41:74:BE:FD:49:B8:85:AB:F2:FC:70:FE:6D:47
    Timestamp : Oct  4 22:14:44.288 2021 GMT
    Extensions: none
    Signature : ecdsa-with-SHA256
                30:45:02:20:48:67:EF:28:F6:F2:B7:C8:F5:0D:7C:3D:
                21:7B:D3:C9:37:4E:B2:7C:AC:70:22:9D:7F:4C:75:D1:
                27:97:89:9C:02:21:00:D9:44:6B:10:0B:F0:6E:2D:99:
                79:77:D7:C8:91:51:C5:E9:50:92:13:EE:99:80:50:FF:
                CB:BD:E6:87:5F:47:A6
Signed Certificate Timestamp:
    Version   : v1 (0x0)
    Log ID    : 51:A3:B0:F5:FD:01:79:9C:56:6D:B8:37:78:8F:0C:A4:
                7A:CC:1B:27:CB:F7:9E:88:42:9A:0D:FE:D4:8B:05:E5
    Timestamp : Oct  4 22:14:44.292 2021 GMT
    Extensions: none
    Signature : ecdsa-with-SHA256
                30:44:02:20:02:92:85:B2:A1:C6:09:18:E5:F4:48:12:
                32:C9:D6:FF:AE:F8:85:DC:E0:06:0D:CB:86:62:5C:E1:
                24:6B:F3:7D:02:20:03:20:01:0B:91:19:AD:4A:87:18:
                FA:5F:A3:98:13:95:CD:EC:8E:1D:63:22:EB:6A:E2:FE:
                33:BC:B1:D8:6C:6B
Signed Certificate Timestamp:
    Version   : v1 (0x0)
    Log ID    : 41:C8:CA:B1:DF:22:46:4A:10:C6:A1:3A:09:42:87:5E:
                4E:31:8B:1B:03:EB:EB:4B:C7:68:F0:90:62:96:06:F6
    Timestamp : Oct  4 22:14:44.184 2021 GMT
    Extensions: none
    Signature : ecdsa-with-SHA256
                30:46:02:21:00:C3:35:6C:A6:27:01:94:88:CF:85:C6:
                3D:33:06:08:DE:BB:14:61:D4:34:8C:AD:A4:24:1B:0F:
                FB:A7:17:13:EA:02:21:00:AE:AB:D7:C2:22:B6:FA:FE:
                7E:20:DA:94:44:18:41:91:DB:98:AC:EA:F8:03:36:57:
                D5:7C:33:4B:71:03:05:9B
                        )

                )

            [verification] => Array
                (
                    [code] => 0
                    [message] => ok
                )

        )

)
```

The **id-ad-caIssuers** property can be followed to get the issuer certificate:

```php
$response = Ssl::show(
    'http://cacerts.digicert.com/CloudflareIncECCCA-3.crt',
    443,
    ['id-ad-caIssuers' => true]
);
```

### stream driver responses

The *stream* driver uses PHP's `stream_*` functions and SSL context to capture entire peer certificate chains.

Each response is indexed. The subindex is the chain sequence with 0 being the host, 1 being the issuer (if not self signed), etc. Results are the return value from [`openssl_x509_parse()`](https://www.php.net/manual/function.openssl-x509-parse.php).


## Custom Drivers

Create a class that extends `Jinomial\LaravelSsl\Drivers\Driver`.

Implement `public function show()` according to the `Jinomial\LaravelSsl\Contracts\Ssl\Driver` contract.

Register a driver factory with the `Jinomial\LaravelSsl\SslManager`.

```php
    /**
     * Application service provider bootstrap for package services.
     *
     * \App\Ssl\Drivers\SslCapture is my custom driver class I made.
     * The SslManager needs to know how to construct it.
     */
    public function boot(): void
    {
        $sslLoader = $this->app->get(\Jinomial\LaravelSsl\SslManager::class);
        $driverName = 'my-custom-driver';
        $sslLoader->extend($driverName, function () use ($driverName) {
            return new \App\Ssl\Drivers\SslCapture($driverName);
        });
    }
```

## Testing

Run all tests:

```bash
composer test
```

Test suites are separated into "unit" and "integration". Run each suite:

```bash
composer test:unit
composer test:integration
```

Tests are grouped into the following groups:

- network
- drivers
- openssl
- manager
- facades
- commands

Run tests for groups:

```bash
composer test -- --include=manager,facades
```

Network tests make remote calls that can take time or fail. Exclude them:

```bash
composer test:unit -- --exclude=network
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Jason Schmedes](https://github.com/jinomial)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
