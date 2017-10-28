# SCSS Compiler for Elgg for Elgg

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