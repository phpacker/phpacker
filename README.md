# phpacker

| Platform | Architectures |
| -------- | ------------- |
| mac      | arm, x64      |
| linux    | arm, x64      |
| windows  | x64           |
| all      | -             |

```bash
# builds mac-arm only
phpacker build mac arm --src=./app.php

# builds for all available platforms/architectures
phpacker build all --src=./app.php
```

Your executables will be generated in `{src-root}/build`. You may change this behaviour using the `--dest=./custom-build-path` option.

## Customize PHP ini

You can define custom PHP ini definitions by using the `--ini` flag.

```bash
# will detect phpacker.ini in the src-root - or prompt interactively
phpacker build all --src=./app.php --ini

# alternatevely you may provide a path
phpacker build all --src=./app.php --ini=./custom-ini-path.ini
```

## Custom PHP extensions

PHPacker uses pre-built staticly linked PHP binaries in order to enable cross-compilation. Custom binaries need to be built on the platforms they are intended for. This can be cumbersome when you do not have access to those machines.

We aim to make this as simple as possible by providing a GitHub Workflow that provides these binaries.

To customize PHP you need to:

- clone the [php-bin](https://github.com/phpacker/php-bin) repo
- update php-extensions.txt - See the [extension list](https://static-php.dev/en/guide/extensions.html) to see what's supported
- run the workflows, merge all pull requests & tag a release

To use your custom binaries you need to set the repository url. You can do this by creating a `phpacker.json` in rour src-root. PHPacker will pick this up automatically during the build.

```json
{
  "bin": "https://github.com/{username}/php-bin"
}
```

Alternatively you may pass a path as a command option

```bash
# you can provide a config path.
# phpacker.json will always be used unless this option is given
phpacker build all --src=./app.php --config=./custom-config-path.json
```
