<?php

declare(strict_types=1);

namespace October\Boost;

use Illuminate\Support\ServiceProvider as ServiceProviderBase;

class ServiceProvider extends ServiceProviderBase
{
    /**
     * register the service provider.
     */
    public function register(): void
    {
        //
    }

    /**
     * boot the service provider.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerToolConfiguration();
        }
    }

    /**
     * registerToolConfiguration publishes the Boost MCP tool configuration.
     */
    protected function registerToolConfiguration(): void
    {
        $this->publishes([
            __DIR__.'/../config/october-boost.php' => config_path('october-boost.php'),
        ], 'october-boost-config');
    }
}
