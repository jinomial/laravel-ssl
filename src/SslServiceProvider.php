<?php

namespace Jinomial\LaravelSsl;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Jinomial\LaravelSsl\Commands\ShowCertificateCommand;

/**
 * @api
 */
class SslServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ssl.php',
            'ssl'
        );

        $this->app->singleton(SslManager::class, function (Application $app) {
            return new SslManager($app);
        });
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
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
}
