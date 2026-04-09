---
name: octobercms-theme-development
description: "Use when creating, modifying, or working with October CMS themes, including pages, layouts, partials, content files, CMS components, Twig markup, or theme assets. Activate when the user mentions themes, Twig templates, CMS pages, page URLs, layout scaffolding, partial rendering, content blocks, or any frontend template task. Do not use for backend admin panel development or plugin PHP code."
license: MIT
metadata:
  author: octobercms
---
# October CMS Theme Development

Themes are file-based and contain all frontend templates. They use Twig for markup and support pages, layouts, partials, and content files.

## Theme Directory Structure

```
themes/mytheme/
├── theme.yaml              ← Theme metadata
├── pages/
│   ├── index.htm
│   ├── blog.htm
│   └── blog/
│       └── post.htm
├── layouts/
│   └── default.htm
├── partials/
│   ├── header.htm
│   ├── footer.htm
│   └── blog/
│       └── post-card.htm
├── content/
│   └── welcome.md
└── assets/
    ├── css/
    ├── js/
    └── images/
```

## Template Structure

CMS templates (pages, layouts, partials) have up to three sections separated by `==`:

1. **Configuration** (INI format) — template parameters
2. **PHP code** (optional) — server-side logic
3. **Twig markup** — the rendered HTML

```
url = "/blog"
layout = "default"
title = "Blog"
==
<?
function onStart()
{
    $this['posts'] = \Acme\Blog\Models\Post::where('is_published', true)->get();
}
?>
==
<h1>Blog</h1>
{% for post in posts %}
    <article>
        <h2>{{ post.title }}</h2>
        {{ post.content|raw }}
    </article>
{% endfor %}
```

## Pages

Pages define URLs and are the entry point for rendering. Configuration properties:

```ini
url = "/blog/post/:slug"
layout = "default"
title = "Blog Post"
description = "Displays a single blog post"
is_hidden = 0

[blogPost]
slug = "{{ :slug }}"
```

- URLs support parameters with `:param` syntax (e.g., `/blog/:slug`).
- Optional parameters use `:param?` syntax.
- Wildcard routes use `:slug*` to capture remaining segments.
- Components are attached in the configuration section using `[componentName]`.

## Layouts

Layouts wrap pages and define the HTML scaffold:

```
description = "Default layout"
==
<!DOCTYPE html>
<html>
<head>
    <title>{{ this.page.title }} - My Site</title>
    {% styles %}
    {% framework extras %}
</head>
<body>
    {% partial "header" %}

    <main>
        {% page %}
    </main>

    {% partial "footer" %}

    {% scripts %}
</body>
</html>
```

Key Twig tags:
- `{% page %}` — renders the page content
- `{% partial "name" %}` — renders a partial
- `{% content "name.md" %}` — renders a content block
- `{% styles %}` — outputs registered CSS
- `{% scripts %}` — outputs registered JS
- `{% framework %}` — includes the AJAX framework
- `{% framework extras %}` — includes AJAX framework with extras (validation, loading indicators, flash messages)

## Partials

Reusable template fragments. Can accept variables:

```twig
{% partial "blog/post-card" post=post %}
```

Inside the partial:
```twig
<article class="post-card">
    <h3>{{ post.title }}</h3>
    <p>{{ post.excerpt }}</p>
</article>
```

## Content Blocks

Static text/HTML/Markdown content that can be edited separately:

```twig
{% content "welcome.md" %}
```

Content files support `.htm`, `.txt`, and `.md` extensions.

## CMS Components

Components are PHP classes that provide frontend functionality. They are attached to pages in the configuration section:

```ini
url = "/blog"
layout = "default"

[blogPosts]
postsPerPage = 10
sortOrder = "published_at desc"
```

Using the component in Twig:

```twig
{# Render the component's default partial #}
{% component 'blogPosts' %}

{# Or access component properties directly #}
{% for post in blogPosts.posts %}
    <h2>{{ post.title }}</h2>
{% endfor %}
```

### Defining a Component

```php
namespace Acme\Blog\Components;

use Cms\Classes\ComponentBase;

class BlogPosts extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Blog Posts',
            'description' => 'Displays a list of blog posts.',
        ];
    }

    public function defineProperties()
    {
        return [
            'postsPerPage' => [
                'title' => 'Posts per page',
                'description' => 'Number of posts to show per page',
                'default' => 10,
                'type' => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'Must be a number',
            ],
            'sortOrder' => [
                'title' => 'Sort order',
                'description' => 'How to sort the posts',
                'type' => 'dropdown',
                'default' => 'published_at desc',
            ],
        ];
    }

    public function getSortOrderOptions()
    {
        return [
            'published_at desc' => 'Newest first',
            'published_at asc' => 'Oldest first',
            'title asc' => 'Title (A-Z)',
        ];
    }

    public function onRun()
    {
        $this->page['posts'] = $this->loadPosts();
    }

    protected function loadPosts()
    {
        return \Acme\Blog\Models\Post::where('is_published', true)
            ->orderByRaw($this->property('sortOrder'))
            ->paginate($this->property('postsPerPage'));
    }

    /**
     * AJAX handler — called via data-request="onLoadMore"
     */
    public function onLoadMore()
    {
        // ...
    }
}
```

### Component Lifecycle

Method | When Called
--- | ---
`init()` | When the component is first initialized
`onRun()` | Before the page is rendered
`onRender()` | Before the component's default partial is rendered
`onSomething()` | AJAX handlers, called on demand

### Component Default Partial

Components can provide a default partial in `components/blogposts/default.htm`:

```
plugins/acme/blog/
└── components/
    ├── BlogPosts.php
    └── blogposts/
        └── default.htm
```

## Twig Reference

### Variables

```twig
{{ variable }}              {# Output escaped #}
{{ variable|raw }}          {# Output unescaped HTML #}
{{ this.page.title }}       {# Current page title #}
{{ this.page.id }}          {# Current page filename #}
{{ this.param.slug }}       {# URL parameter #}
```

### Page Variables

Variable | Description
--- | ---
`this.page` | Current page object (title, url, description, etc.)
`this.layout` | Current layout object
`this.param` | URL parameters (e.g., `this.param.slug`)
`this.environment` | Application environment
`this.controller` | CMS controller instance

### Filters

```twig
{{ 'hello'|upper }}         {# HELLO #}
{{ date|date('F j, Y') }}  {# January 1, 2025 #}
{{ html|raw }}              {# Unescaped output #}
{{ text|e }}                {# HTML escaped #}
{{ items|length }}          {# Count #}
{{ 'slug-text'|page }}      {# Resolve page URL #}
{{ 'image.jpg'|theme }}     {# Theme asset URL #}
{{ 'image.jpg'|media }}     {# Media library URL #}
{{ 'image.jpg'|resize(200, 200) }} {# Resize image #}
```

### Tags

```twig
{% partial "name" variable=value %}
{% content "file.md" %}
{% component "componentAlias" %}
{% page %}
{% styles %}
{% scripts %}
{% framework %}
{% framework extras %}
{% flash %}
    <p data-dismiss="flash">{{ message }}</p>
{% endflash %}
{% placeholder name %}
    Default content here
{% endplaceholder %}
{% put name %}
    Content for placeholder
{% endput %}
```

## PHP Code Section

The PHP code section supports these lifecycle functions:

Function | Available In | Purpose
--- | --- | ---
`onInit()` | Page, Layout | Runs when all components are initialized
`onStart()` | Page, Layout | Runs before the page is rendered
`onEnd()` | Page, Layout | Runs after the page is rendered
`onSomething()` | Page, Layout, Partial | AJAX handler

```php
function onStart()
{
    $this['activeMenu'] = 'blog';
}
```

Variables set with `$this['name'] = value` become available in Twig as `{{ name }}`.

## Common Pitfalls

- Template paths are always absolute from the theme root, even when in a subdirectory: `{% partial "blog/post-card" %}` not `{% partial "post-card" %}`.
- The PHP section only allows function definitions and `use` statements — no loose code.
- Use `{{ variable|raw }}` for HTML content, `{{ variable }}` auto-escapes.
- Component aliases in the INI section become the variable name in Twig.
- Pages must have a `url` property — it cannot be empty.
- The `{% framework %}` tag is required for AJAX functionality to work.
