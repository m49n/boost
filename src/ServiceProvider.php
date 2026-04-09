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
        $this->mergeTools();
    }

    /**
     * mergeTools registers October CMS MCP tools with Laravel Boost.
     */
    protected function mergeTools(): void
    {
        $existing = $this->app['config']->get('boost.mcp.tools.include', []);

        $this->app['config']->set('boost.mcp.tools.include', array_merge($existing, [
            \October\Boost\Tools\GetBlueprints::class,
            \October\Boost\Tools\GetPluginRegistration::class,
            \October\Boost\Tools\GetThemeStructure::class,
        ]));
    }
}