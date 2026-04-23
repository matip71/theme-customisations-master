# Theme Customisations

A handy little plugin to contain your theme/plugin customisation snippets.

> **Note:** This plugin is based on [WooThemes Theme Customisations](http://github.com/woothemes/theme-customisations) and has been extended with a modular SCSS architecture and custom WooCommerce integrations.

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
custom/
├── scss/                          ← Source files (where you work)
│   ├── _variables.scss            ← 10 color variables, typography, breakpoints, spacing
│   ├── _mixins.scss               ← input-base, input-focus, checkbox, breakpoint mixins
│   ├── _global.scss               ← Base, home, breadcrumb
│   ├── _menu.scss                 ← Nav menu
│   ├── _header.scss               ← Header icons, dropdown, search, navigation
│   ├── _footer.scss               ← Footer widgets, social icons
│   ├── _woo-info.scss             ← WooCommerce info banners
│   ├── _shop.scss                 ← Product grid, sorting
│   ├── _product.scss              ← Product detail, sizing form
│   ├── _woo-tables.scss           ← Shared shop_table (cart/checkout/account)
│   ├── _cart.scss                 ← Cart table, mobile grid, desktop layout
│   ├── _mini-cart.scss            ← Mini-cart widget
│   ├── _checkout.scss             ← Checkout forms, mobile reordering
│   ├── _account.scss              ← Account nav, addresses, login/register, orders
│   ├── _checkbox.scss             ← Custom checkboxes (account + checkout)
│   ├── _variation-tooltip.scss    ← Variation tooltip
│   └── style.scss                 ← Entry point
│
├── css/                           ← Compiled output (committed to git)
│   ├── style.css                  ← Compiled CSS
│   └── style.css.map              ← Source maps for DevTools
│
├── functions.php                  ← PHP orchestrator (loads all modules)
├── helpers.php                    ← Shared helper functions
├── acf-fields.php                 ← ACF field definitions
├── class-tf-product-sizing.php    ← WooCommerce product sizing integration
├── account-measures.php           ← Account measurements page
├── custom.js                      ← Custom jQuery scripts
├── variation-tooltip.js           ← Cart variation tooltip logic
├── package.json                   ← npm scripts (dev/build)
└── .gitignore                     ← Ignores node_modules/
```

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
- **PHP** — Add snippets to `custom/functions.php` or create new module files.
- **JavaScript** — Add jQuery snippets to `custom/custom.js`.
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

## License

GPLv2 or later — http://www.gnu.org/licenses/gpl-2.0.html
