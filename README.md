# LGT Installers

`limegreentangerine/installers` is a Composer plugin for setting up
Limegreentangerine projects built with Concrete CMS. It brings in the shared
[ClassKit](https://github.com/limegreentangerine/ClassKit) and
[LGT Toolkit](https://github.com/limegreentangerine/LGTToolkit) packages and
provides commands for installing their project-level resources.

## Requirements

- PHP 8.4 or later
- Composer 2
- Concrete CMS 9.5 or later
- Node.js and npm when installing the ClassKit frontend dependencies

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

The package also requires `limegreentangerine/class_kit` and
`limegreentangerine/lgt_toolkit`; Composer installs these dependencies
automatically.

## Project resources

The package provides two Composer binaries. Run them from the consuming
project's root when you need to provision the corresponding resources:

```sh
php vendor/bin/classkit-resources
php vendor/bin/toolkit-resources
```

- `classkit-resources` installs TypeScript, Svelte, and loader development
  dependencies with npm. Node.js and npm must be available on `PATH`.
- `toolkit-resources` creates `public/.htaccess` with Concrete CMS rewrite
  rules if that file does not already exist. The project must have a `public/`
  directory, and an existing `.htaccess` is left unchanged.

## Included packages

- **ClassKit** provides shared helpers for Concrete CMS package development.
- **LGT Toolkit** provides the LGT baseline of blocks, attributes, dashboard
  utilities, services, and site configuration.

See the respective package documentation for their features and usage:
[ClassKit README](https://github.com/limegreentangerine/ClassKit#readme) and
[LGT Toolkit README](https://github.com/limegreentangerine/LGTToolkit#readme).
