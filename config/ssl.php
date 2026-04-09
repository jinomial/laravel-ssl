<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SSL
    |--------------------------------------------------------------------------
    |
    | This option controls the default SSL driver that is used by the SSL
    | service. Alternative SSL drivers may be setup and used as needed;
    | however, this driver will be used by default.
    |
    */

    'default' => env('SSL_DRIVER', 'openssl'),

    /*
    |--------------------------------------------------------------------------
    | SSL Driver Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the SSL drivers used by your application
    | plus their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Supported: "openssl" or "stream"
    |
    */

    'drivers' => [
 
         // openssl driver uses the openssl binary.
         // requires the openssl binary be in your $PATH.
         'openssl' => [
             'driver' => 'openssl',
         ],

        // file driver parses local certificate files.
        'file' => [
            'driver' => 'file',
        ],

        // stream driver uses stream_* functions and the SSL context.
        // requires the PHP openssl extension.
        'stream' => [
            'driver' => 'stream',
            'timeout' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitored Hosts
    |--------------------------------------------------------------------------
    |
    | Here you may list the hosts you wish to monitor for SSL health. These
    | will be checked by the `ssl:check` Artisan command.
    |
    */

    'monitored_hosts' => [
        // 'google.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warning Threshold
    |--------------------------------------------------------------------------
    |
    | Number of days before expiration to start showing a warning (yellow)
    | in the `ssl:check` command.
    |
    */

    'warning_threshold' => 14,

];
