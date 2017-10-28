# SCSS Compiler for Elgg

![Elgg 2.3](https://img.shields.io/badge/Elgg-2.3-orange.svg?style=flat-square)

## Features

 * Allows you to use `.scss` files directly in the view system
 * Resolves and compiles import paths on the fly (no need for ruby or npm)
 
## Usage

You can extend any `.css` file with `.scss`

```scss
// custom.scss
@import "variables"; // this view is already included and can be extended by other plugins
@import "custom2"; // you can import any other .scss view
@import "external/partial"; // this can be a view residing in another plugin and named as external/_partial.scss  
```

```php
elgg_extend_view('_variables.scss', '_my_variables.scss');
elgg_extend_view('elgg.css', 'custom.scss');
```

Optionally, you can use `'vars','scss'` hook to set global scss variables.

## Conventions

* Prefix your partial scss sheets with an underscore to ensure they are not needlessly compiled, e.g. `_reusable.scss`

* You can use `scss` syntax in your `css` files. This might come handy when you want to overwrite a core css view. It is also easier to work with `.css` files as they are treated as simplecache resources by default.

* The compiler seems to have trouble with full sheets located outside of the root. If you notice that compiler is complaining, add a view to the root and import your files located elsewhere, e.g. if your sheet is importing partials and located in `/views/default/my-theme/elements/sheet.scss`, it may not compile. Add a prefixed sheet to `/views/default/my-theme.sheet.scss` and add `@import "my-theme/elements/sheet";`


