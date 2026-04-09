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
     * mergeTools registers October CMS MCP tools with Laravel Boost
     * and replaces the default search-docs with our own implementation.
     */
    protected function mergeTools(): void
    {
        // Exclude the default search-docs tool (replaced by SearchOctoberDocs)
        $excluded = $this->app['config']->get('boost.mcp.tools.exclude', []);
        $this->app['config']->set('boost.mcp.tools.exclude', array_merge($excluded, [
            \Laravel\Boost\Mcp\Tools\SearchDocs::class,
        ]));

        // Include October CMS tools
        $included = $this->app['config']->get('boost.mcp.tools.include', []);
        $this->app['config']->set('boost.mcp.tools.include', array_merge($included, [
            \October\Boost\Tools\SearchOctoberDocs::class,
            \October\Boost\Tools\GetBlueprints::class,
            \October\Boost\Tools\GetPluginRegistration::class,
            \October\Boost\Tools\GetThemeStructure::class,
        ]));
    }
}