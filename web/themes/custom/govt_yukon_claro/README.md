# Government of Yukon - Claro

## Introduction
A sub-theme of the admin theme Claro that contains various customizations.

## Requirements
- Base Theme: Claro.

## Installation
Enable the theme in Drupal. Run `npm install` to install the packages.

- To compile: `npm run compile`.
- Watch Sass files for changes and compile: `npm run watch`.

The CSS will be compiled to: `css/styles.css`

## Configuration
No configuration needed out of the box.

### Updating Core
There's a Drupal core issue related to theme libraries-override. So you have to
track changes from the core Claro theme within this theme, specifically the
libraries, libraries-override, libraries-extend.

- Related issue: https://www.drupal.org/project/drupal/issues/2642122

When updating Drupal Core, make sure to compare the
`core/themes/claro/claro.info.yml` file to this theme's
`govt_yukon_claro.info.yml` file. Specifically, the libraries need to map 1:1
but the paths within the libraries need to re-mapped to absolute paths.

Example from Claro:

```yml
libraries-override:
  core/drupal.dialog.off_canvas:
    css:
      base:
        misc/dialog/off-canvas.theme.css: css/base/off-canvas.theme.css
```

Copied to this theme and converted to:

```yml
libraries-override:
  core/drupal.dialog.off_canvas:
    css:
      base:
        /core/themes/stable/css/core/dialog/off-canvas.theme.css: /core/themes/claro/css/base/off-canvas.theme.css
```
