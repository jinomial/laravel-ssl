<?php

namespace Jinomial\LaravelSsl;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Jinomial\LaravelSsl\Commands\ShowCertificateCommand;

class SslServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ssl.php',
            'ssl'
        );

        $this->app->singleton('ssl', function ($app) {
            return new SslManager($app);
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot()
    {
        // Publish configuration files.
        $this->publishes([
            __DIR__.'/../config/ssl.php' => config_path('ssl.php'),
        ], 'laravel-ssl-config');

        // Register console commands.
        if ($this->app->runningInConsole()) {
            $this->commands([
                ShowCertificateCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'ssl',
        ];
    }
}
