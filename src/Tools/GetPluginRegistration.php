<?php

declare(strict_types=1);

namespace October\Boost\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetPluginRegistration extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'List all installed October CMS plugins with their registration details, including components, permissions, navigation, and settings. Use "summary" mode first to see all plugins, then request details for a specific plugin. Useful for understanding what plugins are available before extending them.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->boolean()
                ->description('Return only plugin identifiers, names, and descriptions. Defaults to true.'),
            'plugin' => $schema->string()
                ->description('Get full registration details for a specific plugin, e.g. "Acme.Blog".'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $summary = $request->get('summary', true);
        $pluginId = $request->get('plugin');

        try {
            $manager = \System\Classes\PluginManager::instance();

            if ($pluginId) {
                return $this->getPluginDetails($manager, $pluginId);
            }

            return $this->getPluginSummary($manager, $summary);
        }
        catch (\Throwable $e) {
            return Response::error('Failed to read plugins: '.$e->getMessage());
        }
    }

    /**
     * getPluginDetails returns full details for a single plugin.
     */
    protected function getPluginDetails($manager, string $pluginId): Response
    {
        $plugin = $manager->findByIdentifier($pluginId);

        if (!$plugin) {
            return Response::error("Plugin '{$pluginId}' not found.");
        }

        $details = $plugin->pluginDetails();

        $data = [
            'identifier' => $pluginId,
            'name' => $details['name'] ?? '',
            'description' => $details['description'] ?? '',
            'author' => $details['author'] ?? '',
            'icon' => $details['icon'] ?? '',
        ];

        if (property_exists($plugin, 'require')) {
            $data['require'] = $plugin->require ?? [];
        }

        if (method_exists($plugin, 'registerComponents')) {
            $components = $plugin->registerComponents();
            if ($components) {
                $data['components'] = array_map(fn($alias) => $alias, $components);
            }
        }

        if (method_exists($plugin, 'registerPermissions')) {
            $permissions = $plugin->registerPermissions();
            if ($permissions) {
                $data['permissions'] = array_keys($permissions);
            }
        }

        if (method_exists($plugin, 'registerNavigation')) {
            $nav = $plugin->registerNavigation();
            if ($nav) {
                $data['navigation'] = array_keys($nav);
            }
        }

        if (method_exists($plugin, 'registerSettings')) {
            $settings = $plugin->registerSettings();
            if ($settings) {
                $data['settings'] = array_keys($settings);
            }
        }

        return Response::json($data);
    }

    /**
     * getPluginSummary returns a list of all plugins.
     */
    protected function getPluginSummary($manager, bool $summary): Response
    {
        $plugins = [];

        foreach ($manager->getPlugins() as $id => $plugin) {
            $details = $plugin->pluginDetails();

            $entry = [
                'identifier' => $id,
                'name' => $details['name'] ?? '',
                'description' => $details['description'] ?? '',
            ];

            if (!$summary) {
                $entry['author'] = $details['author'] ?? '';

                if (method_exists($plugin, 'registerComponents') && $plugin->registerComponents()) {
                    $entry['components'] = array_values($plugin->registerComponents());
                }

                if (method_exists($plugin, 'registerPermissions') && $plugin->registerPermissions()) {
                    $entry['permissions'] = array_keys($plugin->registerPermissions());
                }
            }

            $plugins[] = $entry;
        }

        return Response::json(['plugins' => $plugins]);
    }
}
