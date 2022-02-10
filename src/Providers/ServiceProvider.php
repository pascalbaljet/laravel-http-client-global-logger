<?php

namespace Onlime\LaravelHttpClientGlobalLogger\Providers;

use Illuminate\Http\Client\PendingRequest;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Onlime\LaravelHttpClientGlobalLogger\Mixins\PendingRequestMixin;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->mergeConfigFrom($this->configFileLocation(), 'http-client-global-logger');

        if (config('http-client-global-logger.enabled') &&
            !config('http-client-global-logger.mixin')) {
            $this->app->register(EventServiceProvider::class);
        }
    }

    public function boot()
    {
        $this->publishes([
            $this->configFileLocation() => config_path('http-client-global-logger.php'),
        ], 'config');

        $channel = config('http-client-global-logger.channel');
        if (!array_key_exists($channel, config('logging.channels'))) {
            // Define new logging channel
            // see https://stackoverflow.com/a/59791539/5982842
            $this->app->make('config')->set("logging.channels.$channel", [
                'driver' => 'monolog',
                'level' => 'debug',
                'handler' => StreamHandler::class,
                'with' => [
                    'stream' => config('http-client-global-logger.logfile'),
                ],
            ]);
        }

        // Mixin variant of using Http:log($name) instead of global logging
        if (config('http-client-global-logger.mixin')) {
            PendingRequest::mixin(new PendingRequestMixin());
        }
    }

    /**
     * Get package config file location.
     */
    protected function configFileLocation(): string
    {
        return realpath(__DIR__ . '/../../config/http-client-global-logger.php');
    }
}
