# Theme Customisations

A handy little plugin to contain your theme/plugin customisation snippets.

> **Note:** This plugin is based on [WooThemes Theme Customisations](http://github.com/woothemes/theme-customisations) and has been extended with a modular SCSS architecture, domain-specific PHP/JS modules, and custom WooCommerce integrations.

## Description

Think of this plugin as an alternative to adding code snippets to the `functions.php` or `style.css` file in your child theme. It keeps all of your changes in one location, independent of the other components that make up your web site. That means you can safely perform theme/plugin updates without the worry of losing your modifications, as well as easily deactivating your customisations to check for conflicts.

## Requirements

- WordPress 3.0.0+
- [Node.js](https://nodejs.org/) (for SCSS compilation)

## Installation

1. Upload `theme-customisations` to the `/wp-content/plugins/` directory
2. Install SCSS dependencies (see [Local Development](#local-development))
3. Activate the plugin through the **Plugins** menu in WordPress
4. Done!

## Project Structure

```
theme-customisations.php               ← Plugin entry point (enqueue CSS/JS, template overrides)

custom/
├── php/                               ← Server-side modules
│   ├── functions.php                  ← Orchestrator — loads all modules (no business logic)
│   ├── helpers.php                    ← Shared helpers: sizing utils, measure formatters, DRY
│   ├── acf-fields.php                 ← ACF field definitions (medidas config)
│   ├── class-tf-product-sizing.php    ← WooCommerce product sizing integration
│   ├── account-measures.php           ← My Account → measurements page
│   ├── header.php                     ← Storefront header icons & user dropdown
│   ├── product-pricing.php            ← Variable product: show min price instead of range
│   └── product-add-to-cart.php        ← PRG redirect to prevent duplicate cart additions
│
├── js/                                ← Client-side modules (domain-specific, per-page)
│   ├── header.js                      ← Injects account icon into Storefront header
│   ├── product.js                     ← Variation price sync, sticky bar, custom sizing fields
│   ├── checkout.js                    ← Smooth slide-up animations for checkout toggles
│   ├── account.js                     ← Mobile login/register tab switcher
│   └── variation-tooltip.js           ← Cart/checkout variation tooltips (hover + click)
│
├── scss/                              ← Source styles (where you work)
│   ├── _variables.scss                ← Color tokens, typography, breakpoints, spacing
│   ├── _mixins.scss                   ← input-base, input-focus, checkbox, breakpoint mixins, etc
│   ├── _global.scss                   ← Base, home, breadcrumb
│   ├── _home.scss                     ← Homepage hero, product cards, editorial layout
│   ├── _menu.scss                     ← Nav menu
│   ├── _header.scss                   ← Header icons, dropdown, search, navigation
│   ├── _footer.scss                   ← Footer widgets, social icons
│   ├── _woo-info.scss                 ← WooCommerce info banners
│   ├── _shop.scss                     ← Product grid, sorting
│   ├── _product.scss                  ← Product detail, sizing form
│   ├── _woo-tables.scss               ← Shared shop_table (cart/checkout/account)
│   ├── _cart.scss                     ← Cart table, mobile grid, desktop layout
│   ├── _mini-cart.scss                ← Mini-cart widget
│   ├── _checkout.scss                 ← Checkout forms, mobile reordering
│   ├── _account.scss                  ← Account nav, addresses, login/register, orders
│   ├── _checkbox.scss                 ← Custom checkboxes (account + checkout)
│   ├── _variation-tooltip.scss        ← Variation tooltip
│   └── style.scss                     ← Entry point — imports all partials
│
├── css/                               ← Compiled output (committed to git)
│   ├── style.css                      ← Compiled CSS (DON'T EDIT THIS FILE)
│   └── style.css.map                  ← Source maps for DevTools (DON'T EDIT THIS FILE)
│
├── package.json                       ← npm scripts (dev/build)
└── .gitignore                         ← Ignores node_modules/
```

## Architecture

### Plugin Entry Point (`theme-customisations.php`)

The main plugin class handles three responsibilities:

1. **CSS enqueue** — loads `custom/css/style.css` with `filemtime()` versioning for automatic cache busting.
2. **JS enqueue** — iterates over a registry of domain-specific scripts (`header.js`, `product.js`, etc.) and enqueues each with its own `filemtime()` version.
3. **Template overrides** — supports both top-level WordPress templates and WooCommerce template overrides from `custom/templates/`.

### PHP Modules (`custom/php/`)

The orchestrator (`functions.php`) is a lightweight loader — no business logic, just `require_once` calls. Each module is self-contained and registers its own hooks:

| Module | Responsibility |
|---|---|
| `helpers.php` | Shared pure functions: `tf_get_product_standard_sizes()`, `tf_format_measure_label()`, `tf_has_custom_measures()`, `tf_get_user_defaults()`, `tf_product_requires_sizing()` |
| `acf-fields.php` | ACF field definitions for custom measurements (medidas config) |
| `class-tf-product-sizing.php` | WooCommerce sizing integration: renders the sizing form, saves data to cart, displays in order |
| `account-measures.php` | My Account endpoint for saved body measurements |
| `header.php` | Storefront header icons & authenticated user dropdown |
| `product-pricing.php` | Replaces the variable product price range with just the minimum price |
| `product-add-to-cart.php` | Post/Redirect/Get pattern to prevent duplicate cart additions on refresh |

### JS Modules (`custom/js/`)

Each file is an IIFE scoped to `jQuery` and targets a specific page or feature:

| Module | Responsibility |
|---|---|
| `header.js` | Injects the account icon `<template>` into the Storefront header |
| `product.js` | Syncs variation price with main display & sticky bar, toggles custom sizing fields |
| `checkout.js` | Smooth `slideUp` animations for "Ship to different address" and "Create account" toggles |
| `account.js` | Mobile login/register tab switcher for the My Account page |
| `variation-tooltip.js` | Converts variation `<dl>` elements into interactive hover/click tooltips; re-initializes on WooCommerce AJAX events |

## Local Development

### First-time setup

```bash
cd wp-content/plugins/theme-customisations-master/custom
npm install
```

### Development (watch mode)

Automatically recompiles CSS on every save:

```bash
npm run dev
```

This outputs `css/style.css` (expanded, with source maps) — you can inspect the original `.scss` files directly in browser DevTools.

### Production build

Generates a minified CSS file without source maps:

```bash
npm run build
```

## Usage

- **CSS/SCSS** — Edit the files in `custom/scss/`. Never edit `custom/css/style.css` directly, as it's overwritten on every build.
- **PHP** — Add new feature modules in `custom/php/` and register them in `custom/php/functions.php`.
- **JavaScript** — Add new domain-specific scripts in `custom/js/` and register them in the `$scripts` array inside `theme-customisations.php`.
- **WooCommerce templates** — Place overrides in `custom/templates/woocommerce/` (create the directory if needed).
- **Top-level templates** — Place template files (e.g. `page.php`, `single.php`) in `custom/templates/`.

## Design Tokens

All design values are centralized in `scss/_variables.scss`:

| Token | Value | Usage |
|---|---|---|
| `$black` | `#000000` | Borders, backgrounds, text emphasis |
| `$white` | `#ffffff` | Backgrounds, inverted text |
| `$text-primary` | `#333333` | Main body text |
| `$text-muted` | `#969696` | Secondary/muted text |
| `$text-tertiary` | `#727272` | Tertiary text |
| `$border` | `#dadad6` | Borders |
| `$surface` | `#e9e9e5` | Soft backgrounds, separators |
| `$surface-card` | `#fcfcfc` | Card backgrounds |
| `$surface-alt` | `#f1f1f1` | Header widget backgrounds |
| `$surface-light` | `#fafafa` | Expandable section backgrounds |

## FAQ

### What templates can I override?

You can override top-level templates (`page.php`, `single.php`, etc.) by placing them in `custom/templates/`. Template partials like `header.php` or `footer.php` are not supported.

### How do I add a new PHP module?

1. Create the file in `custom/php/` (e.g. `my-feature.php`).
2. Add `require_once __DIR__ . '/my-feature.php';` to `custom/php/functions.php`.
3. Register your own hooks inside the new file.

### How do I add a new JS script?

1. Create the file in `custom/js/` (e.g. `my-feature.js`).
2. Add an entry to the `$scripts` array in `theme-customisations.php` (e.g. `'tf-my-feature' => 'my-feature.js'`).
3. The script will be auto-versioned via `filemtime()`.

