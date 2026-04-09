# October CMS

This application uses **October CMS**, a Laravel-based content management system with its own conventions and architecture. October CMS patterns take precedence over standard Laravel patterns.

## Critical Differences from Laravel

- **Do not suggest** Livewire, Inertia.js, Blade components, or Laravel Folio — October CMS has its own frontend architecture.
- **Do not suggest** Laravel form requests for validation — October uses model-based validation via the `Validation` trait.
- **Do not suggest** Laravel controllers with route model binding — October uses backend controllers with behaviors.
- **Do not suggest** `resources/views/` Blade templates — October uses Twig-based CMS themes in the `themes/` directory and PHP-based partials in `controllers/` and `models/` directories.
- **Do not use** `php artisan make:model` or `php artisan make:controller` — October has its own scaffolding commands: `php artisan create:plugin`, `php artisan create:model`, `php artisan create:controller`, `php artisan create:component`.

## Architecture Overview

October CMS is built on these pillars:

- **Plugins** — modular packages in `plugins/{author}/{name}/` that extend the CMS. Each has a `Plugin.php` registration file extending `PluginBase`.
- **Themes** — file-based frontend templates in `themes/{name}/` using Twig markup with pages, layouts, partials, and content files.
- **Backend** — admin panel powered by controller behaviors (FormController, ListController, RelationController) with YAML-driven configuration.
- **Tailor** — headless CMS feature using YAML blueprints to define content structures without writing code.
- **AJAX Framework** — built-in AJAX system using `data-request` attributes or the `jax` JavaScript API to call server-side handlers.

## Plugin Structure

All custom code lives in plugins. A typical plugin structure:

```
plugins/acme/blog/
├── Plugin.php              ← Registration file
├── controllers/
│   └── Posts.php           ← Backend controller
│       └── posts/          ← Controller views directory
│           ├── config_list.yaml
│           ├── config_form.yaml
│           ├── _list_toolbar.php
│           ├── index.php
│           ├── create.php
│           └── update.php
├── models/
│   └── Post.php            ← Eloquent model
│       └── post/           ← Model config directory
│           ├── fields.yaml
│           └── columns.yaml
├── components/
│   └── BlogPost.php        ← CMS component
├── updates/
│   ├── version.yaml        ← Version history
│   └── create_posts_table.php
└── lang/
    └── en/
        └── lang.php
```

## Model Conventions

October CMS models extend `Model` (aliased from `October\Rain\Database\Model`) and use **array-based relationship definitions** instead of Laravel's fluent methods:

```php
class Post extends Model
{
    use \October\Rain\Database\Traits\Validation;

    protected $table = 'acme_blog_posts';

    public $rules = [
        'title' => 'required',
        'slug' => 'required|unique:acme_blog_posts',
    ];

    protected $jsonable = ['metadata'];

    public $belongsTo = [
        'category' => \Acme\Blog\Models\Category::class,
    ];

    public $hasMany = [
        'comments' => [\Acme\Blog\Models\Comment::class, 'delete' => true],
    ];

    public $attachOne = [
        'featured_image' => \System\Models\File::class,
    ];

    public $attachMany = [
        'gallery' => \System\Models\File::class,
    ];
}
```

Key differences from Laravel models:
- Relationships are defined as **public array properties** (`$hasOne`, `$hasMany`, `$belongsTo`, `$belongsToMany`, `$morphTo`, `$morphOne`, `$morphMany`, `$morphToMany`, `$morphedByMany`, `$attachOne`, `$attachMany`), not fluent methods.
- Validation is handled by the `Validation` trait with `$rules`, `$customMessages`, and `$attributeNames` properties.
- File attachments use `$attachOne` and `$attachMany` with `System\Models\File`.
- JSON columns use the `$jsonable` property (not `$casts`).
- Table names follow the pattern `{author}_{plugin}_{plural_name}` (e.g., `acme_blog_posts`).
- Model events use method overrides (`beforeCreate`, `afterSave`, etc.) not closures.

## Backend Controllers

Backend controllers use behaviors defined in YAML configuration files:

```php
class Posts extends \Backend\Classes\Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
}
```

## AJAX Framework

Use `data-request` attributes to call server-side handlers:

```html
<form data-request="onSubmit" data-request-update="{ result: '#resultDiv' }">
    <input name="name" />
    <button type="submit">Send</button>
</form>
```

Handlers are PHP functions prefixed with `on`:

```php
function onSubmit()
{
    $this['result'] = input('name');
}
```

## Artisan Commands

- `php artisan create:plugin Acme.Blog` — scaffold a new plugin
- `php artisan create:model Acme.Blog Post` — scaffold a new model
- `php artisan create:controller Acme.Blog Posts` — scaffold a new controller
- `php artisan create:component Acme.Blog Post` — scaffold a new component
- `php artisan october:migrate` — run all plugin migrations
- `php artisan october:fresh` — destroy and recreate the database
- `php artisan plugin:refresh Acme.Blog` — refresh a plugin's migrations

## Conventions

- Check sibling files for existing patterns before writing new code.
- Follow the naming conventions: `Author\Plugin` namespace, snake_case table names, StudlyCase class names.
- Use `~/plugins/acme/blog/models/post/fields.yaml` path notation with `~` prefix for absolute plugin paths in YAML configs.
- Use `$/acme/blog/models/post/fields.yaml` path notation with `$` prefix as an alternative absolute path syntax.
