# Government of Yukon
> The Government of Yukon Drupal front-end base theme.

A theme based on the Government of Yukon's branding and customized from Drupal's
starterkit_theme.

- [Starterkit Documentation](https://www.drupal.org/docs/core-modules-and-themes/core-themes/starterkit-theme)

## Introduction
This theme provides base styling, components and branding assets for the Government of Yukon.

This theme uses:
- [Bootstrap v5.2.3](https://getbootstrap.com/docs/5.2/getting-started/introduction/)
- [Font Awesome v5](https://fontawesome.com/v5/docs/web/use-with/sass)
- [Accessible Slick Slider v1.0.1](https://accessible360.github.io/accessible-slick/)

## Requirements
- [Twig Tweak](https://www.drupal.org/project/twig_tweak)

## Installation
Enable the theme in Drupal. Run `npm install` to install the packages.

- To compile: `npm run compile`.
- Watch Sass files for changes and compile: `npm run watch`.

The CSS will be compiled to: `css/*.css`.

## Configuration
No configuration needed out of the box.

## Scripts

## Web Accessibility
You can use `pa11y` to scan URLs for any accessibility issues.
- `npm run pa11y http://example.lndo.site/contact-us`.

## Library Usage

### Font Awesome
Icon styles included:
- Solid (`fas`).
- Regular (`far`).
- Brands (`fab`).

Sass example for using a Font Awesome icon inside a button:

```
.btn-sample-with-icon {
  &::after {
    @extend %fa-icon;
    @extend .fas;
    content: fa-content($fa-var-chevron-right);
  }
}
```

HTML icon example:

```
<span class="fas fa-chevron-right"></span>
```
