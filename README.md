![PHPacker](https://github.com/phpacker/phpacker/blob/main/art/readme-logo.jpg?raw=true)

[![tests](https://github.com/phpacker/phpacker/actions/workflows/pest.yml/badge.svg)](https://github.com/phpacker/phpacker/actions/workflows/pest.yml)
[![analyze](https://github.com/phpacker/phpacker/actions/workflows/phpstan.yml/badge.svg)](https://github.com/phpacker/phpacker/actions/workflows/phpstan.yml)
[![format](https://github.com/phpacker/phpacker/actions/workflows/pint.yml/badge.svg)](https://github.com/phpacker/phpacker/actions/workflows/pint.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpacker/phpacker.svg)](https://packagist.org/packages/phpacker/phpacker)
[![License](https://img.shields.io/github/license/phpacker/phpacker.svg)](LICENSE.md)

PHPacker enables you to package any PHP script or PHAR into a standalone, cross-platform executable. It handles all the complexity of bundling PHP runtime with your application, making distribution simple and hassle-free.

<br />

## Installation

You can install PHPacker globally via Composer:

```bash
composer global require phpacker/phpacker
```

Or as a project dependency:

```bash
composer require phpacker/phpacker --dev
```

## Quick Start

Build an executable from your PHP script with a single command:

```bash
phpacker build --src=./app.php
```

## Digging deeper

### Basic Build Commands

When you don't provide any input you'll be prompted through setting the basics. You may also pass these as arguments to the build command.

```bash
# Build for specific platform and architecture
phpacker build mac arm --src=./app.phar

# Build for all supported platforms
phpacker build all --src=./app.phar

# Build with custom output directory
phpacker build --src=./app.phar --dest=./custom-build-path

# Build with with php version
phpacker build --src=./app.phar --php=8.3
```

### Supported Platforms

| Platform | Architectures | PHP Versions  |
| -------- | ------------- | ------------- |
| macOS    | arm64, x64    | 8.2, 8.3, 8.4 |
| Linux    | arm64, x64    | 8.2, 8.3, 8.4 |
| Windows  | x64           | 8.2, 8.3, 8.4 |

```php
use Symfony\Component\Filesystem\Path;

$appName = 'my-app';

// Define APP_DATA constant
define('APP_DATA', match (PHP_OS_FAMILY) {
    'Darwin' => Path::join(getenv('HOME'), 'Library', 'Application', 'Support', $appName),
    'Windows' => Path::join(getenv('LOCALAPPDATA'), $appName),
    default => Path::join(getenv('HOME'), $appName))
});
```

This ensures your application's data is stored in the appropriate location across different operating systems:

- macOS: `~/Library/Application Support/.my-app`
- Windows: `%LOCALAPPDATA%\my-app`
- Linux: `~/.my-app`

### Configuration

Using a config file you are able to predefine any argument or option otherwise passed to the build command. This way you can have all parameters for your project in a single version tracked file.

By using this method you'll be able to run the `build` command without providing any input:

```bash
phpacker build
```

#### JSON Configuration (phpacker.json)

Place a `phpacker.json` file in your project root to define build settings:

```json
{
  "src": "./bin/app.phar",
  "dest": "./build",
  "ini": "./phpacker.ini",
  "platform": "all",
  "php": "8.4",
  "repository": "optional/custom-php-bin-repo"
}
```

PHPacker will look for a config file in the following order:

1. Custom path specified via `--config=path/to/file.json`
2. `phpacker.json` in the source directory via `--src` option
3. `phpacker.json` in the current working directory

#### PHP INI Configuration

Similarly PHPacker will look for ini configuration in the following order:

1. Custom path specified via `--ini=path/to/file.ini`
2. Path specified in discovered config file
3. `phpacker.ini` in the source directory via `--src` option
4. `phpacker.ini` in the current working directory
5. Interactive prompt if `--ini` is passed without a value

## Prepping your CLI app

When building an executable using PHPacker, there are some important considerations:

### Single PHP Script Limitations

If you're building from a single PHP script, all code must be contained within that file. External dependencies through `require` or `use` statements are not supported. If your application needs external dependencies, you should first package it as a PHAR archive using a tool like [humbug/box](https://github.com/box-project/box).

### File System Access

When your application is packaged (either from a single script or PHAR), it cannot write files within the application itself since everything is combined into a single executable. Instead, use the platform-specific application data directory for file storage. Here's a helper script to determine the correct path:

## Custom PHP Builds

PHPacker supports custom PHP builds with specific extensions through our [php-bin](https://github.com/phpacker/php-bin) repository.

To create a custom build:

1. Fork the [php-bin](https://github.com/phpacker/php-bin) repository
2. Modify `php-extensions.txt` ([supported extensions](https://static-php.dev/en/guide/extensions.html))
3. Run the GitHub Workflows
4. Tag a release

Use custom builds by specifying your repository:

```bash
phpacker build all --repository="your-org/php-bin"
```

Or from a config file:

```json
{
  "repository": "your-org/php-bin"
}
```

## Updating PHP Binaries

PHPacker automatically checks for binary updates during builds. Manual updates can be performed:

```bash
# Update official binaries
phpacker download

# Update custom repository
phpacker download "your-org/php-bin"
```

## Contributing

Contributions are welcome! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details.

## Support

- [Documentation](https://phpacker.github.io/docs)
- [GitHub Issues](https://github.com/phpacker/phpacker/issues)
