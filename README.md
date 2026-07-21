# Fig

A tiny static plugin loader for view templates. Each "fig" is a plain PHP function named `fig_<name>` living in its own file; `fig` finds it, `include`s it on first use, and calls it — so templates can call `fig::date(...)` without every helper being loaded up front.

## Example

```php
// bootstrap, once per request
fig::configure($configFig, $data); // $data implements DataInterface

// in a template
echo fig::date('now', 'F jS, Y');   // loads figs/fig_date.php, then calls fig_date('now', 'F jS, Y')
echo fig::e($someHtml);             // loads figs/fig_e.php (escape helper)
```

Register additional plugin directories, checked in order alongside the bundled `src/figs/` folder:

```php
fig::addPath('/app/view_plugins');       // appended, searched last
fig::addPath('/app/overrides', true);    // prepended, searched first
```

A plugin file just defines a function:

```php
// figs/fig_shout.php
function fig_shout(string $text): string
{
    return strtoupper($text) . '!';
}
```

Calling an unregistered plugin (`fig::doesNotExist()`) throws `FigException`.
