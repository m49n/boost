<p align="center"><img src="https://octobercms.com/themes/website/assets/images/october-color-logo.svg" width="200" alt="October CMS"></p>

## Introduction

October CMS Boost extends [Laravel Boost](https://github.com/laravel/boost) with October CMS-specific guidelines, skills, and tools that teach AI agents how to write idiomatic October CMS code.

While Laravel Boost provides the foundation (database tools, log inspection, documentation search), October CMS Boost adds the layer that understands plugins, Tailor blueprints, backend controllers, CMS themes, the AJAX framework, and October's model conventions.

## Requirements

- October CMS 4.x
- PHP 8.1+
- [Laravel Boost](https://github.com/laravel/boost) ^1.0 or ^2.0

## Installation

```bash
composer require october/boost --dev
```

Then run the Boost installer to configure your AI agent:

```bash
php artisan boost:install
```

During installation, select **october/boost** when prompted to include third-party guidelines.

## What's Included

### Guidelines

A core guideline file that teaches AI agents the fundamental differences between October CMS and standard Laravel:

- Don't suggest Livewire, Inertia, or Blade components
- Use array-based model relationships, not fluent methods
- Use October's scaffolding commands, not Laravel's
- Follow October's plugin, theme, and backend conventions

### Skills

On-demand knowledge modules that AI agents activate when working in specific domains:

Skill | Activates When
--- | ---
`octobercms-plugin-development` | Creating/modifying plugins, Plugin.php, migrations, registration
`octobercms-tailor-development` | Working with Tailor blueprints, content fields, entry records
`octobercms-backend-controllers` | Building backend pages with FormController, ListController, RelationController
`octobercms-theme-development` | Creating themes, pages, layouts, partials, Twig templates, CMS components
`octobercms-ajax-framework` | Using data-request attributes, jax API, AJAX handlers, partial updates
`octobercms-model-development` | Defining models, relationships, validation, traits, events

### MCP Tools

Custom tools that give AI agents real-time access to your October CMS application:

Tool | Purpose
--- | ---
`GetBlueprints` | List and inspect Tailor blueprint definitions
`GetPluginRegistration` | List plugins with their components, permissions, navigation
`GetThemeStructure` | Inspect the active theme's pages, layouts, and partials

To register the MCP tools, add them to your `config/boost.php`:

```php
'mcp' => [
    'tools' => [
        'include' => [
            \October\Boost\Tools\GetBlueprints::class,
            \October\Boost\Tools\GetPluginRegistration::class,
            \October\Boost\Tools\GetThemeStructure::class,
        ],
    ],
],
```

## How It Works

October CMS Boost uses Laravel Boost's extension system:

- **Guidelines** are auto-discovered from `resources/boost/guidelines/` — no configuration needed.
- **Skills** are auto-discovered from `resources/boost/skills/` — AI agents activate them on-demand when the task matches.
- **MCP Tools** are registered via `config/boost.php` and provide live introspection of your October CMS application.

## License

October CMS Boost is open-sourced software licensed under the [MIT license](LICENSE.md).
