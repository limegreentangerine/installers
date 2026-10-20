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

The package provides two Composer binaries. Run them from the consuming
project's root:

```sh
php vendor/bin/install-lgt-resources
php vendor/bin/uninstall-lgt-resources
```

`install-lgt-resources` installs the ClassKit and ThemeKit frontend dependencies
with npm, creates the Concrete CMS rewrite file, scaffolds a ThemeKit theme, and
attempts to install the ClassKit and LGT Toolkit packages in Concrete CMS. Node.js
and npm must be available on `PATH`. The command prompts for a theme handle
(letters, numbers, hyphens, and underscores).

The theme is created under `public/application/themes/<handle>`, with Sass and
TypeScript source folders under `src/themes/<handle>`. The installer also copies
frontend build configuration files to the directory above the project root,
adds system page views under `public/application/views`, and configures theme
paths in `public/application/config/app.php`. Existing files in the theme and
source folders are kept; back up any existing root build configuration files
before running the command.

If `public/.htaccess` already exists, the installer leaves it unchanged and
stops after the ClassKit resource setup. To run the remaining setup stages,
ensure that file does not exist before running the installer.

Failures to install the ClassKit or LGT Toolkit Concrete packages are printed
to STDERR, but do not stop the installer from attempting the next package.
Failures in other setup steps, including npm installation, can stop the command.

The plugin runs `install-lgt-resources` when ClassKit, LGT Toolkit, or ThemeKit
is installed and after Composer updates. These automatic runs use the same
interactive installer and may prompt for a theme handle.

`uninstall-lgt-resources` removes ClassKit frontend dependencies and generated
ClassKit TypeScript/configuration resources. It does not uninstall the Concrete
CMS packages or remove ThemeKit resources.

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
