# Limegreentangerine Installers

`limegreentangerine/installers` is a Composer plugin for setting up
Limegreentangerine projects built with Concrete CMS. It brings in the shared
[ClassKit](https://github.com/limegreentangerine/ClassKit) and
[LGT Toolkit](https://github.com/limegreentangerine/LGTToolkit) packages, along
with [ThemeKit](https://github.com/limegreentangerine/ThemeKit), and provides
commands for installing their project-level resources.

## Requirements

- PHP 8.4 or later
- Composer 2
- Concrete CMS 9.5 or later
- Node.js and npm when installing ClassKit or ThemeKit frontend dependencies

## Install in a Concrete CMS project

Composer 2 requires explicit permission to load plugins. Add this to the
consuming project's root `composer.json` before requiring this package:

```json
{
    "config": {
        "allow-plugins": {
            "limegreentangerine/installers": true
        }
    }
}
```

Then install the package from the project root:

```sh
composer require limegreentangerine/installers
```

The allowlist entry is important: without it Composer will not activate this
plugin. If the project already has a `config` or `allow-plugins` section, add
the entry there rather than replacing the existing settings.

The package also requires `limegreentangerine/class_kit`,
`limegreentangerine/lgt_toolkit`, and `limegreentangerine/theme_kit`; Composer
installs these dependencies automatically.

## Project resources

The package provides three Composer binaries. Run them from the consuming
project's root when you need to provision the corresponding resources:

```sh
php vendor/bin/classkit-resources
php vendor/bin/toolkit-resources
php vendor/bin/themekit-resources
```

When `limegreentangerine/class_kit` is installed, the plugin runs
`classkit-resources` and installs the Concrete package automatically. After a
Composer update, it runs `classkit-resources` and updates the Concrete package.

- `classkit-resources` installs TypeScript, Svelte, and loader development
  dependencies with npm. Node.js and npm must be available on `PATH`.
- `toolkit-resources` creates `public/.htaccess` with Concrete CMS rewrite
  rules if that file does not already exist. The project must have a `public/`
  directory, and an existing `.htaccess` is left unchanged.
- `themekit-resources` prompts for a theme handle (letters, numbers, hyphens,
  and underscores), installs the theme's npm dependencies, and scaffolds its
  Concrete CMS theme under `public/application/themes/<handle>`. It also
  creates Sass and TypeScript source folders under `src/themes/<handle>`,
  copies frontend build configuration files to the directory above the project
  root, adds system page views under `public/application/views`, and configures
  the theme paths in `public/application/config/app.php`. Existing files in
  the theme and source folders are kept; back up any existing root build
  configuration files before running the command.

## Included packages

- **ClassKit** provides shared helpers for Concrete CMS package development.
- **LGT Toolkit** provides the Limegreentangerine baseline of blocks, attributes,
  dashboard utilities, services, and site configuration.
- **ThemeKit** provides starter files and frontend tooling for building
  Concrete CMS themes.

See the respective package documentation for their features and usage:
[ClassKit README](https://github.com/limegreentangerine/ClassKit#readme),
[LGT Toolkit README](https://github.com/limegreentangerine/LGTToolkit#readme),
and [ThemeKit README](https://github.com/limegreentangerine/ThemeKit#readme).
