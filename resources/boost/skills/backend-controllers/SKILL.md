---
name: octobercms-backend-controllers
description: "Use when creating, modifying, or debugging October CMS backend controllers, including form controllers, list controllers, relation controllers, import/export controllers, or reorder controllers. Activate when working with config_form.yaml, config_list.yaml, config_relation.yaml, fields.yaml, columns.yaml, scopes.yaml, controller views, toolbar partials, or any backend page behavior. Do not use for CMS theme pages or frontend components."
license: MIT
metadata:
  author: octobercms
---
# October CMS Backend Controllers

Backend controllers power the admin panel. They use **behaviors** - composable mixins configured via YAML files - to provide list, form, and relation management features.

## Scaffolding

```bash
php artisan create:controller Acme.Blog Posts
```

This creates:
- `controllers/Posts.php` - the controller class
- `controllers/posts/` - views directory with config files

## Controller Structure

```php
namespace Acme\Blog\Controllers;

class Posts extends \Backend\Classes\Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['acme.blog.manage_posts'];

    public function __construct()
    {
        parent::__construct();

        \BackendMenu::setContext('Acme.Blog', 'blog', 'posts');
    }
}
```

## Available Behaviors

Behavior | Purpose | Config Property
--- | --- | ---
`FormController` | Create, update, preview forms | `$formConfig`
`ListController` | Sortable, searchable record lists | `$listConfig`
`RelationController` | Manage model relationships in forms | `$relationConfig`
`ReorderController` | Drag-and-drop record reordering | `$reorderConfig`
`ImportExportController` | CSV import and export | `$importExportConfig`

## List Configuration (config_list.yaml)

```yaml
title: Blog Posts
list: $/acme/blog/models/post/columns.yaml
modelClass: Acme\Blog\Models\Post
recordUrl: acme/blog/posts/update/:id
recordsPerPage: 20
defaultSort:
    column: created_at
    direction: desc
toolbar:
    buttons: list_toolbar
    search:
        prompt: Search posts...
filter: $/acme/blog/models/post/scopes.yaml
```

### List Columns (columns.yaml)

```yaml
columns:
    title:
        label: Title
        type: text
        searchable: true
        sortable: true
    category:
        label: Category
        relation: category
        select: name
        sortable: true
    is_published:
        label: Published
        type: switch
    created_at:
        label: Created
        type: datetime
```

### List Filters (scopes.yaml)

```yaml
scopes:
    is_published:
        label: Published
        type: switch
        conditions: is_published = :filtered
    category:
        label: Category
        modelClass: Acme\Blog\Models\Category
        nameFrom: name
```

### Toolbar Partial (_list_toolbar.php)

```php
<div data-control="toolbar">
    <a href="<?= Backend::url('acme/blog/posts/create') ?>"
        class="btn btn-primary oc-icon-plus">
        New Post
    </a>
    <button
        type="button"
        class="btn btn-danger oc-icon-trash"
        data-request="onDelete"
        data-list-checked-trigger
        data-list-checked-request
        data-request-confirm="Delete selected posts?">
        Delete Selected
    </button>
</div>
```

## Form Configuration (config_form.yaml)

```yaml
name: Blog Post
form: $/acme/blog/models/post/fields.yaml
modelClass: Acme\Blog\Models\Post
defaultRedirect: acme/blog/posts
create:
    title: New Blog Post
    redirect: acme/blog/posts/update/:id
    redirectClose: acme/blog/posts
update:
    title: Edit Blog Post
    redirect: acme/blog/posts
    redirectClose: acme/blog/posts
preview:
    title: View Blog Post
```

### Form Fields (fields.yaml)

```yaml
fields:
    title:
        label: Title
        type: text
        span: full
    slug:
        label: Slug
        type: text
        span: full
        preset:
            field: title
            type: slug

tabs:
    fields:
        content:
            label: Content
            type: richeditor
            size: huge
            tab: Content
        featured_image:
            label: Featured Image
            type: fileupload
            mode: image
            tab: Media
        category:
            label: Category
            type: relation
            tab: Categories

secondaryTabs:
    fields:
        is_published:
            label: Published
            type: switch
            tab: Settings
        published_at:
            label: Publish Date
            type: datepicker
            tab: Settings
```

### Common Field Types

Type | Description
--- | ---
`text` | Single-line text
`textarea` | Multi-line text
`number` | Number input
`dropdown` | Dropdown select (uses `getFieldNameOptions()` on model)
`radio` | Radio buttons
`checkbox` | Single checkbox
`checkboxlist` | Multiple checkboxes
`switch` | Toggle switch
`datepicker` | Date/time picker
`colorpicker` | Color picker
`richeditor` | Rich text HTML editor
`markdown` | Markdown editor
`codeeditor` | Code editor
`fileupload` | File upload
`relation` | Relation dropdown/list
`repeater` | Repeatable field groups
`partial` | Render a custom partial
`section` | Visual section divider
`hint` | Help text block

### Field Properties

Property | Description
--- | ---
`label` | Field label
`type` | Field widget type
`span` | `auto`, `full`, `left`, `right`, `row`, `storm`
`tab` | Tab name for tabbed forms
`comment` | Help text below the field
`commentAbove` | Help text above the field
`placeholder` | Placeholder text
`default` | Default value
`required` | Show required indicator (visual only, use model `$rules` for validation)
`disabled` | Disable the field
`hidden` | Hide the field
`cssClass` | Custom CSS class
`readOnly` | Read-only field
`context` | Show only in specific contexts: `create`, `update`, `preview`
`dependsOn` | Other fields this field depends on for dynamic updates
`trigger` | Show/hide based on another field's value
`preset` | Auto-populate from another field

## Relation Configuration (config_relation.yaml)

```yaml
comments:
    label: Comments
    view:
        list: $/acme/blog/models/comment/columns.yaml
        toolbarButtons: create|delete
    manage:
        form: $/acme/blog/models/comment/fields.yaml
```

## Controller Views

Views are PHP files in the controller's views directory:

```php
<!-- controllers/posts/index.php -->
<?php Block::put('breadcrumb') ?>
    <li><span>Blog</span></li>
    <li class="active"><span>Posts</span></li>
<?php Block::endPut() ?>

<?= $this->listRender() ?>
```

```php
<!-- controllers/posts/create.php -->
<?php Block::put('breadcrumb') ?>
    <li><a href="<?= Backend::url('acme/blog/posts') ?>">Posts</a></li>
    <li class="active"><span>New Post</span></li>
<?php Block::endPut() ?>

<?= $this->formRender() ?>
```

```php
<!-- controllers/posts/update.php -->
<?php Block::put('breadcrumb') ?>
    <li><a href="<?= Backend::url('acme/blog/posts') ?>">Posts</a></li>
    <li class="active"><span>Edit Post</span></li>
<?php Block::endPut() ?>

<?= $this->formRender() ?>
```

## Extending Controllers

### Adding Fields Dynamically

```php
Posts::extendFormFields(function ($form, $model, $context) {
    if (!$model instanceof \Acme\Blog\Models\Post) {
        return;
    }

    $form->addTabFields([
        'custom_field' => [
            'label' => 'Custom Field',
            'type' => 'text',
            'tab' => 'Custom',
        ],
    ]);
});
```

### Adding Columns Dynamically

```php
Posts::extendListColumns(function ($list, $model) {
    if (!$model instanceof \Acme\Blog\Models\Post) {
        return;
    }

    $list->addColumns([
        'custom_column' => [
            'label' => 'Custom',
            'type' => 'text',
        ],
    ]);
});
```

## Controller Behavior Overrides

Controllers can override behavior methods to customize form and list behavior:

### Form Overrides

```php
class Posts extends \Backend\Classes\Controller
{
    // Before/after form save
    public function formBeforeSave($model) { }
    public function formAfterSave($model) { }

    // Before/after create specifically
    public function formBeforeCreate($model) { }
    public function formAfterCreate($model) { }

    // Before/after update specifically
    public function formBeforeUpdate($model) { }
    public function formAfterUpdate($model) { }

    // Before/after delete
    public function formAfterDelete($model) { }

    // Extend the form query
    public function formExtendQuery($query) { }

    // Extend form fields programmatically
    public function formExtendFields($form) { }

    // Extend field configuration before rendering
    public function formExtendFieldsBefore($form) { }

    // Modify the form model
    public function formExtendModel($model) { }

    // Extend the refresh data for specific fields
    public function formExtendRefreshData($form, $fields) { }
}
```

### List Overrides

```php
class Posts extends \Backend\Classes\Controller
{
    // Extend the list query
    public function listExtendQuery($query, $definition = null) {
        $query->where('is_archived', false);
    }

    // Extend list columns programmatically
    public function listExtendColumns($list) { }

    // Modify records after retrieval
    public function listExtendRecords($records, $definition = null) { }

    // Modify list filter scopes
    public function listFilterExtendScopes($filter) { }

    // Override how list records are retrieved
    public function listFilterExtendQuery($query, $scope) { }
}
```

## Import/Export Controller

### Configuration (config_import_export.yaml)

```yaml
import:
    title: Import Subscribers
    modelClass: Acme\Campaign\Models\SubscriberImport
    list: $/acme/campaign/models/subscriber/columns.yaml

export:
    title: Export Subscribers
    modelClass: Acme\Campaign\Models\SubscriberExport
    list: $/acme/campaign/models/subscriber/columns.yaml
    fileName: subscribers.csv
```

### Import Model

```php
class SubscriberImport extends \Backend\Models\ImportModel
{
    public $rules = [];

    public function importData($results, $sessionKey = null)
    {
        foreach ($results as $row => $data) {
            try {
                $subscriber = new Subscriber;
                $subscriber->fill($data);
                $subscriber->save();
                $this->logCreated();
            }
            catch (\Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
    }
}
```

Log methods: `logCreated()`, `logUpdated()`, `logError($row, $message)`, `logWarning($row, $message)`, `logSkipped($row, $message)`.

### Export Model

```php
class SubscriberExport extends \Backend\Models\ExportModel
{
    public function exportData($columns, $sessionKey = null)
    {
        $records = Subscriber::all();
        $records->each(function ($record) use ($columns) {
            $record->addVisible($columns);
        });
        return $records->toArray();
    }
}
```

### Controller View (import_export.php)

```php
<?php Block::put('breadcrumb') ?>
    <li><span>Import / Export</span></li>
<?php Block::endPut() ?>

<div class="padded-container">
    <?= $this->importExportRender() ?>
</div>
```

## Reorder Controller

### Configuration (config_reorder.yaml)

```yaml
title: Reorder Categories
modelClass: Acme\Blog\Models\Category
nameFrom: title
```

The model must use the `Sortable` or `NestedTree` trait. The controller view:

```php
<?= $this->reorderRender() ?>
```

## Additional Filter Scope Types

```yaml
scopes:
    is_published:
        label: Published
        type: switch
        conditions: is_published = :filtered
    category:
        label: Category
        type: group
        modelClass: Acme\Blog\Models\Category
        nameFrom: name
    created_at:
        label: Created
        type: daterange
        conditions: created_at >= ':after' AND created_at <= ':before'
    status:
        label: Status
        type: group
        options:
            draft: Draft
            published: Published
            archived: Archived
        conditions: status = :filtered
```

Available filter scope types: `group`, `switch`, `date`, `daterange`, `text`, `number`, `numberrange`, `checkbox`, `dropdown`, `clear`.

## Custom AJAX Handlers in Controllers

Controllers can define AJAX handlers called from backend pages:

```php
class Posts extends \Backend\Classes\Controller
{
    // ...

    public function onPublishSelected()
    {
        $checkedIds = post('checked', []);

        \Acme\Blog\Models\Post::whereIn('id', $checkedIds)
            ->update(['is_published' => true]);

        \Flash::success('Posts published successfully.');

        return $this->listRefresh();
    }
}
```

## Common Pitfalls

- Always set `BackendMenu::setContext()` in the constructor to highlight the correct menu.
- Config YAML paths use `$/` or `~/` prefix for absolute plugin paths.
- The `relation` field type in forms requires `RelationController` behavior to be implemented on the controller.
- Use `$requiredPermissions` to restrict controller access - this is an array of permission codes.
- Form views (`create.php`, `update.php`) must exist even if they just call `$this->formRender()`.
- The list toolbar partial name should match what's specified in `config_list.yaml` under `toolbar.buttons`.
- Use `$this->listRefresh()` and `$this->formRefreshField('field')` to update the UI from AJAX handlers.
- Import/Export models extend `Backend\Models\ImportModel` and `Backend\Models\ExportModel` - not the regular plugin model.
- Controller behavior overrides (e.g., `formBeforeSave`) are methods on the controller, not the model.
- Multiple list definitions use array syntax: `$listConfig = ['posts' => 'config_list.yaml', 'comments' => 'config_comments_list.yaml']`.
