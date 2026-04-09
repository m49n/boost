---
name: octobercms-plugin-development
description: "Use when creating, modifying, or scaffolding October CMS plugins. Activate when working with Plugin.php registration files, plugin directory structure, version.yaml files, migrations, registering components, permissions, navigation, settings, form widgets, or any plugin lifecycle task. Also use when the user mentions creating a new plugin from scratch. Do not use for theme-only or frontend-only tasks."
license: MIT
metadata:
  author: octobercms
---
# October CMS Plugin Development

## Scaffolding

Use `php artisan create:plugin Acme.Blog` to scaffold a new plugin. This creates the directory structure and registration file.

Additional scaffolding commands:
- `php artisan create:model Acme.Blog Post` — model with migration
- `php artisan create:controller Acme.Blog Posts` — backend controller
- `php artisan create:component Acme.Blog BlogPost` — CMS component
- `php artisan create:command Acme.Blog MyCommand` — artisan command

## Plugin Registration File

Every plugin has a `Plugin.php` extending `System\Classes\PluginBase`:

```php
namespace Acme\Blog;

class Plugin extends \System\Classes\PluginBase
{
    /**
     * @var array require these plugins
     */
    public $require = ['October.Drivers'];

    public function pluginDetails()
    {
        return [
            'name' => 'Blog',
            'description' => 'Provides blog features.',
            'author' => 'Acme',
            'icon' => 'icon-pencil',
        ];
    }

    public function register()
    {
        // Called when plugin is first registered (DI, singletons)
    }

    public function boot()
    {
        // Called on every request (event listeners, extending models)
    }

    public function registerComponents()
    {
        return [
            \Acme\Blog\Components\Post::class => 'blogPost',
        ];
    }

    public function registerPermissions()
    {
        return [
            'acme.blog.manage_posts' => [
                'label' => 'Manage Blog Posts',
                'tab' => 'Blog',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'blog' => [
                'label' => 'Blog',
                'url' => \Backend::url('acme/blog/posts'),
                'icon' => 'icon-pencil',
                'permissions' => ['acme.blog.*'],
                'order' => 500,
                'sideMenu' => [
                    'posts' => [
                        'label' => 'Posts',
                        'icon' => 'icon-file-text-o',
                        'url' => \Backend::url('acme/blog/posts'),
                        'permissions' => ['acme.blog.manage_posts'],
                    ],
                ],
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'Blog Settings',
                'description' => 'Manage blog settings.',
                'category' => 'Blog',
                'icon' => 'icon-cog',
                'class' => \Acme\Blog\Models\Settings::class,
                'order' => 500,
            ],
        ];
    }

    public function registerFormWidgets()
    {
        return [
            \Acme\Blog\FormWidgets\MyWidget::class => 'mywidget',
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'acme.blog::mail.post-notification',
        ];
    }
}
```

### Registration Methods

Method | Purpose
--- | ---
`pluginDetails()` | Plugin metadata (name, description, author, icon)
`register()` | Service registration, singletons (called first)
`boot()` | Event listeners, model extensions (called after all plugins registered)
`registerComponents()` | CMS frontend components
`registerPermissions()` | Backend permission definitions
`registerNavigation()` | Backend menu items and side menus
`registerSettings()` | Settings pages (model-based or URL-based)
`registerFormWidgets()` | Custom form field widgets
`registerMailTemplates()` | Mail template definitions
`registerSchedule($schedule)` | Task scheduler definitions
`registerConsoleCommand($key, $class)` | Artisan commands

## Version History

The `updates/version.yaml` file tracks versions and references migration scripts:

```yaml
v1.0.1: First version
v1.0.2: Added categories
v1.0.3:
    - Added comments support
    - create_comments_table.php
v1.0.4:
    - Seeded default categories
    - seed_categories.php
```

## Migrations

Migration files live in `updates/` and use Laravel's schema builder:

```php
<?php namespace Acme\Blog\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('acme_blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('acme_blog_posts');
    }
}
```

Table naming convention: `{author}_{plugin}_{plural_name}` in snake_case (e.g., `acme_blog_posts`).

## Dependencies

Declare plugin dependencies with the `$require` property:

```php
public $require = ['Acme.User', 'October.Drivers'];
```

Dependencies are installed automatically and the plugin is disabled if dependencies are missing.

## Extending Other Plugins

Use the `boot()` method to extend models and controllers from other plugins:

```php
public function boot()
{
    // Add a relation to another plugin's model
    \Acme\User\Models\User::extend(function ($model) {
        $model->hasMany['posts'] = \Acme\Blog\Models\Post::class;
    });

    // Extend a backend controller
    \Acme\User\Controllers\Users::extendFormFields(function ($form, $model, $context) {
        if (!$model instanceof \Acme\User\Models\User) {
            return;
        }

        $form->addTabFields([
            'posts' => [
                'label' => 'Blog Posts',
                'type' => 'partial',
                'path' => '$/acme/blog/controllers/posts/_user_posts.php',
                'tab' => 'Blog',
            ],
        ]);
    });
}
```

## Common Pitfalls

- Always use `PluginBase` not Laravel's `ServiceProvider` for plugin registration.
- The `register()` method runs before all plugins are loaded — do not reference other plugins here; use `boot()` instead.
- Table names must be globally unique — always prefix with author and plugin name.
- Never modify migrations that have run in production — create new migration files instead.
- The `$require` array uses dot notation (`Acme.Blog`), not namespace notation.
