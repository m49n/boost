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

MCP tools are registered automatically - no manual configuration needed.

## What's Included

### Guidelines

A core guideline file loaded into every AI conversation that covers:

- Critical differences from Laravel (don't suggest Livewire, Inertia, Blade, etc.)
- Architecture overview (plugins, themes, backend, Tailor, AJAX)
- Model conventions (array-based relationships, Validation trait)
- Event system and settings model patterns
- Artisan commands and naming conventions

### Skills

On-demand knowledge modules that AI agents activate when working in specific domains:

Skill | Covers
--- | ---
`octobercms-plugin-development` | Plugin.php, registration, migrations, settings models, events, console commands, localization, mail templates
`octobercms-tailor-development` | Blueprints, content fields, entry records, multisite, navigation, columns
`octobercms-backend-controllers` | Form/List/Relation/Import-Export/Reorder controllers, behavior overrides, filter scopes, YAML configs
`octobercms-theme-development` | Pages, layouts, partials, Twig, components, theme.yaml customization, error pages, asset compilation
`octobercms-ajax-framework` | Data attributes, jax API, handlers, partial updates, file uploads, validation, turbo router
`octobercms-model-development` | Relationships, validation, traits, events, accessors/mutators, deferred bindings, eager loading, extending models

### MCP Tools

Custom tools that give AI agents real-time access to your October CMS application:

Tool | Purpose
--- | ---
`SearchOctoberDocs` | Search official documentation for Laravel, October CMS, Larajax, and Meloncart
`GetBlueprints` | List and inspect Tailor blueprint definitions
`GetPluginRegistration` | List plugins with their components, permissions, navigation
`GetThemeStructure` | Inspect the active theme's pages, layouts, and partials

## How It Works

October CMS Boost uses Laravel Boost's extension system:

- **Guidelines** are auto-discovered from `resources/boost/guidelines/` - no configuration needed.
- **Skills** are auto-discovered from `resources/boost/skills/` - AI agents activate them on-demand when the task matches.
- **MCP Tools** are auto-registered via the service provider - they appear alongside Laravel Boost's built-in tools.

## License

October CMS Boost is open-sourced software licensed under the [MIT license](LICENSE.md).
