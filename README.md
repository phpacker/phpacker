![PHPacker](https://github.com/phpacker/phpacker/blob/main/art/readme-logo.jpg?raw=true)

[![tests](https://github.com/phpacker/phpacker/actions/workflows/pest.yml/badge.svg)](https://github.com/phpacker/phpacker/actions/workflows/pest.yml)
[![analyze](https://github.com/phpacker/phpacker/actions/workflows/phpstan.yml/badge.svg)](https://github.com/phpacker/phpacker/actions/workflows/phpstan.yml)
[![format](https://github.com/phpacker/phpacker/actions/workflows/pint.yml/badge.svg)](https://github.com/phpacker/phpacker/actions/workflows/pint.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpacker/phpacker.svg)](https://packagist.org/packages/phpacker/phpacker)
[![License](https://img.shields.io/github/license/phpacker/phpacker.svg)](LICENSE.md)

PHPacker enables you to package any PHP script or PHAR into a standalone, cross-platform executable. It handles all the complexity of bundling PHP with your application, making distribution simple and hassle-free.

<br />

## Documentation

You can read the official documentation on the [PHPacker website](https://phpacker.dev).

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

<br />

## Quick Start

Build a standalone executable from your PHP script with a single command:

```bash
phpacker build --src=./app.phar
```

<br />

## Contributing

Contributions are welcome! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details.

## Support

- [Documentation](https://phpacker.github.io/docs)
- [GitHub Issues](https://github.com/phpacker/phpacker/issues)
