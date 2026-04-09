---
name: octobercms-tailor-development
description: "Use when creating, editing, or working with October CMS Tailor blueprints, content fields, entry records, streams, structures, globals, singles, or mixins. Activate when the user mentions Tailor, blueprints, content management without code, headless CMS features, or YAML-based content definitions. Also use when working with EntryRecord models or blueprint handles. Do not use for traditional plugin model/controller development."
license: MIT
metadata:
  author: octobercms
---
# October CMS Tailor Development

Tailor is October CMS's headless CMS feature that lets you define content structures using YAML blueprints, without writing any PHP code or database migrations.

## Blueprint Types

Type | Purpose
--- | ---
`entry` | Content entries with optional drafts and versions
`stream` | Ordered collection of records (like blog posts)
`structure` | Tree/hierarchy of records (like categories)
`global` | Single global record (like site settings)
`single` | Single record per site (like an about page)
`mixin` | Reusable field groups included by other blueprints

## Blueprint File Location

Blueprints are YAML files stored in the theme or registered by plugins:

```
themes/mytheme/blueprints/
├── blog/
│   ├── post.yaml        ← Stream blueprint
│   └── category.yaml    ← Structure blueprint
├── pages/
│   └── about.yaml       ← Single blueprint
└── globals/
    └── settings.yaml    ← Global blueprint
```

Or registered by plugins in `plugins/acme/blog/blueprints/`.

## Blueprint YAML Structure

### Stream Blueprint (ordered collection)

```yaml
handle: Blog\Post
type: stream
name: Blog Post
drafts: true

primaryNavigation:
    label: Blog
    icon: icon-pencil
    order: 200

fields:
    title:
        label: Title
        type: text
        validation:
            - required

    slug:
        label: Slug
        type: text
        preset:
            field: title
            type: slug

    content:
        label: Content
        type: richeditor
        size: large

    featured_image:
        label: Featured Image
        type: fileupload
        mode: image
        maxFiles: 1

    category:
        label: Category
        type: entries
        source: Blog\Category
        maxItems: 1

    tags:
        label: Tags
        type: entries
        source: Blog\Tag
```

### Structure Blueprint (tree hierarchy)

```yaml
handle: Blog\Category
type: structure
name: Blog Category

fields:
    title:
        label: Title
        type: text
        validation:
            - required

    description:
        label: Description
        type: textarea
```

### Global Blueprint

```yaml
handle: Blog\Config
type: global
name: Blog Configuration

primaryNavigation:
    label: Blog Settings
    icon: icon-cog
    order: 300

fields:
    posts_per_page:
        label: Posts Per Page
        type: number
        default: 10

    show_sidebar:
        label: Show Sidebar
        type: switch
        default: true
```

### Mixin Blueprint (reusable fields)

```yaml
handle: Blog\MetaFields
type: mixin
name: SEO Meta Fields

fields:
    meta_title:
        label: Meta Title
        type: text

    meta_description:
        label: Meta Description
        type: textarea
        size: small
```

Using a mixin in another blueprint:

```yaml
fields:
    title:
        label: Title
        type: text

    seo:
        type: mixin
        source: Blog\MetaFields
```

## Common Content Field Types

Type | Description
--- | ---
`text` | Single-line text input
`textarea` | Multi-line text area
`number` | Number input
`richeditor` | Rich text editor (HTML)
`markdown` | Markdown editor
`switch` | Toggle on/off
`dropdown` | Dropdown select
`radio` | Radio button group
`checkbox` | Checkbox
`checkboxlist` | Multiple checkboxes
`fileupload` | File upload (supports `mode: image`)
`repeater` | Repeatable field groups
`entries` | Relation to other Tailor entries
`datepicker` | Date/time picker
`colorpicker` | Color picker
`codeeditor` | Code editor

## Querying Tailor Entries

In CMS pages or components, use the entry handle to query records:

```php
use Tailor\Models\EntryRecord;

// Get all published posts
$posts = EntryRecord::inSection('Blog\Post')
    ->where('is_enabled', true)
    ->orderBy('published_at', 'desc')
    ->get();

// Get a single entry by slug
$post = EntryRecord::inSection('Blog\Post')
    ->where('slug', $slug)
    ->first();

// Get global values
$config = EntryRecord::inSection('Blog\Config')->first();
$postsPerPage = $config->posts_per_page;

// Get structure as tree
$categories = EntryRecord::inSection('Blog\Category')
    ->getNested();
```

## Tailor Components in Themes

October CMS provides built-in Twig components for rendering Tailor content:

```twig
{# List entries #}
{% collection posts = "Blog\Post" %}
    {% for post in posts %}
        <h2>{{ post.title }}</h2>
        {{ post.content|raw }}
    {% endfor %}
{% endcollection %}

{# Single global #}
{% global config = "Blog\Config" %}
    Posts per page: {{ config.posts_per_page }}
{% endglobal %}
```

## Field Validation

Add validation rules directly in blueprint fields:

```yaml
fields:
    email:
        label: Email
        type: text
        validation:
            - required
            - email

    age:
        label: Age
        type: number
        validation:
            - required
            - integer
            - min:0
            - max:150
```

## Common Pitfalls

- Blueprint handles must be globally unique across the application.
- The `handle` uses backslash notation (`Blog\Post`), not dot notation.
- Use `entries` field type for relations between Tailor records, not `relation`.
- Tailor auto-generates database tables — you do not write migrations.
- Changes to blueprint fields may require running `php artisan october:migrate` to update the database schema.
- Global blueprints have only one record — query with `->first()`.
- Use `drafts: true` on the blueprint to enable draft/publish workflow.
