---
name: octobercms-ajax-framework
description: "Use when working with October CMS AJAX features, including data-request attributes, the jax JavaScript API, AJAX handlers, partial updates, form serialization, loading indicators, flash messages, validation, or the turbo router. Activate when the user mentions AJAX requests, data-request, onSomething handlers, partial rendering, or dynamic page updates without full reload. Do not use for standard REST API endpoints or non-AJAX server-side code."
license: MIT
metadata:
  author: octobercms
---
# October CMS AJAX Framework

October CMS includes a built-in AJAX framework (powered by Larajax) that allows dynamic page updates without full browser refreshes. It works in both CMS themes and the backend admin panel.

## Including the Framework

Add the framework to your layout:

```twig
{% framework %}          {# Core AJAX functionality #}
{% framework extras %}   {# Adds validation, loading indicators, flash messages, turbo router #}
```

## Data Attributes API

The simplest way to make AJAX requests - no JavaScript required:

### Basic Request

```html
<button data-request="onDoSomething">Click Me</button>
```

### Form Submission

```html
<form data-request="onSubmit">
    <input name="email" type="email" />
    <button type="submit">Subscribe</button>
</form>
```

Form data is automatically serialized and sent with the request.

### Updating Partials

```html
<form data-request="onCalculate" data-request-update="{ result: '#resultDiv' }">
    <input name="value1" /> + <input name="value2" />
    <button type="submit">Calculate</button>
</form>

<div id="resultDiv"></div>
```

The `data-request-update` attribute maps partial names to CSS selectors. After the handler runs, the named partial is rendered and injected into the target element.

### Available Data Attributes

Attribute | Description
--- | ---
`data-request` | Handler name (required), e.g., `onSubmit`
`data-request-update` | Partials to update: `{ partial: '#selector' }`
`data-request-confirm` | Confirmation message before sending
`data-request-redirect` | Redirect URL after success
`data-request-loading` | CSS selector of element to show during request
`data-request-success` | JavaScript to execute on success
`data-request-error` | JavaScript to execute on error
`data-request-complete` | JavaScript to execute on completion (success or error)
`data-request-data` | Extra data to send: `{ key: 'value' }`
`data-request-form` | CSS selector of form to serialize (when outside a form)
`data-request-flash` | Enable flash messages from the server
`data-request-before-update` | JavaScript to execute before partials are updated
`data-request-download` | Enable file download response
`data-track-input` | Auto-submit on input change (with debounce)

### Prepend and Append

Use `@` prefix to prepend or append instead of replacing:

```html
<!-- Append to the list -->
data-request-update="{ 'list-item': '@#itemList' }"

<!-- Prepend to the list -->
data-request-update="{ 'list-item': '^#itemList' }"
```

### Self-Updating Partials

Use `#` to refer to the partial's own container:

```html
data-request-update="{ 'self': '#' }"
```

## JavaScript API

For more control, use the `jax` JavaScript object:

### Basic Request

```js
jax.ajax('onDoSomething');
```

### Form Request

```js
jax.request('#myForm', 'onSubmit', {
    update: { result: '#resultDiv' },
    success: function(data) {
        console.log('Done', data);
    },
    error: function(data) {
        console.log('Error', data);
    }
});
```

### With Extra Data

```js
jax.ajax('onLoadItems', {
    data: { page: 2, category: 'news' },
    update: { 'items-list': '#itemContainer' }
});
```

### Options

Option | Description
--- | ---
`update` | Object mapping partials to selectors
`data` | Extra data to send
`success` | Success callback function
`error` | Error callback function
`complete` | Completion callback function
`confirm` | Confirmation message string
`redirect` | Redirect URL on success
`loading` | Loading indicator selector
`form` | Form element to serialize
`flash` | Enable flash messages (boolean)
`download` | Enable file download (boolean or filename)
`browserValidate` | Enable browser validation (boolean)

## Writing AJAX Handlers

### In CMS Pages/Layouts

Define handlers in the PHP code section:

```php
function onSubmitContactForm()
{
    $name = input('name');
    $email = input('email');

    // Process the form...

    $this['confirmation'] = "Thanks, {$name}!";
}
```

### In CMS Components

Define handlers as methods on the component class:

```php
class ContactForm extends ComponentBase
{
    public function onSubmit()
    {
        $data = post();

        // Validate and process...

        \Flash::success('Message sent!');
    }
}
```

Call component handlers with the component alias prefix:

```html
<button data-request="contactForm::onSubmit">Submit</button>
```

### In Backend Controllers

Define handlers as methods on the controller:

```php
class Posts extends \Backend\Classes\Controller
{
    public function onBulkPublish()
    {
        $ids = post('checked', []);
        Post::whereIn('id', $ids)->update(['is_published' => true]);
        \Flash::success('Posts published.');
        return $this->listRefresh();
    }
}
```

## Handler Responses

### Returning Data

```php
function onGetStats()
{
    return [
        'totalUsers' => 500,
        'totalPosts' => 1200,
    ];
}
```

Access in JavaScript:
```html
<button data-request="onGetStats" data-request-success="alert(data.totalUsers)">
    Get Stats
</button>
```

### Partial Updates from PHP

```php
function onUpdateList()
{
    $this['items'] = Item::all();

    return [
        '#itemList' => $this->makePartial('item-list'),
    ];
}
```

### Redirects

```php
function onSave()
{
    // Save logic...

    return \Redirect::to('/thank-you');
}
```

### Flash Messages

```php
function onSave()
{
    // Save logic...

    \Flash::success('Record saved successfully.');
    \Flash::error('Something went wrong.');
    \Flash::warning('Please check your input.');
}
```

Display flash messages in Twig:

```twig
{% flash %}
    <p data-dismiss="flash" class="flash-{{ type }}">
        {{ message }}
    </p>
{% endflash %}
```

### Dispatching Browser Events

```php
function onUpdateProfile()
{
    // Update logic...

    $this->dispatchBrowserEvent('profile:updated', ['name' => 'Jeff']);
}
```

Listen in JavaScript:

```js
addEventListener('profile:updated', function (event) {
    console.log('Updated:', event.detail.name);
});
```

### Throwing AJAX Exceptions

```php
function onValidate()
{
    throw new \AjaxException([
        'error' => 'Validation failed',
        'fields' => ['email' => 'Email is required'],
    ]);
}
```

## Form Validation

With `{% framework extras %}`, validation errors are automatically displayed:

```php
function onRegister()
{
    $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
    ];

    $validation = \Validator::make(input(), $rules);

    if ($validation->fails()) {
        throw new \ValidationException($validation);
    }

    // Process registration...
}
```

Validation errors automatically appear next to form fields when using `{% framework extras %}`.

## Loading Indicators

With `{% framework extras %}`, a loading indicator is shown automatically during AJAX requests. Customize with:

```html
<!-- Show a specific element during loading -->
<button data-request="onProcess" data-request-loading="#spinner">
    Process
</button>
<span id="spinner" style="display: none">Loading...</span>
```

## Turbo Router

With `{% framework extras turbo %}`, page navigation becomes AJAX-powered (like Turbo/Pjax):

```twig
{% framework extras turbo %}
```

This intercepts link clicks and form submissions, loading pages without full browser refreshes.

## File Uploads via AJAX

Upload files using a standard form with `files: true` in the JavaScript API:

```js
jax.request('#uploadForm', 'onUpload', {
    files: true
});
```

Or with data attributes using `enctype`:

```html
<form data-request="onUpload" enctype="multipart/form-data">
    <input type="file" name="attachment" />
    <button type="submit">Upload</button>
</form>
```

Handle the upload in PHP:

```php
function onUpload()
{
    $file = \Input::file('attachment');

    $model = new \System\Models\File;
    $model->data = $file;
    $model->save();

    // Attach to a model
    $post = Post::find(input('post_id'));
    $post->featured_image()->add($model);
}
```

Note: The backend `fileupload` form widget handles file uploads automatically via deferred bindings - you don't need to write AJAX handlers for standard form file fields.

## Common Pitfalls

- Handler names must start with `on` (e.g., `onSubmit`, `onLoadMore`).
- The `data-request` attribute must be inside a `<form>` tag for form data to be serialized, or use `data-request-form` to specify a form.
- Use `input('key')` or `post('key')` in handlers to access request data, not `$_POST`.
- Component handlers need the alias prefix: `componentAlias::onHandler`.
- The `{% framework %}` tag must be present in the layout for AJAX to work.
- Partial names in `data-request-update` do not include the `.htm` extension.
- Use `$this->makePartial()` in PHP handlers when returning partial markup manually.
- The generic `onAjax` handler is available everywhere without defining it - useful for updating partials without server logic.
