<?php

namespace PHPacker\PHPacker\Support\Config;

use BadMethodCallException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Command\Concerns\InteractsWithFiles;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function Laravel\Prompts\info;

/**
 * @method static object all()
 * @method static mixed get(string $key)
 * @method static mixed set(string $key, mixed $value)
 * @method static object merge(array $data)
 */
class ConfigManager
{
    use InteractsWithFiles;

    protected static ConfigRepository $repository;

    protected static bool $loaded = false;

    const INTERNAL_CONFIG = __DIR__ . '/../../../config/phpacker.json';

    const PROXY_METHODS = [
        'get',
        'set',
        'all',
        'merge',
    ];

    // Proxy methods to the repository object
    public static function __callStatic($method, $arguments)
    {
        if (in_array($method, self::PROXY_METHODS)) {
            return self::$repository->$method(...$arguments);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    public static function getRepository(): ConfigRepository
    {
        return self::$repository;
    }

    public static function bootstrap(EventDispatcherInterface $dispatcher)
    {
        // Init static config repository
        self::$repository = new ConfigRepository(
            self::readJsonFile(self::INTERNAL_CONFIG),
        );

        // Dynamically merge config based on command input
        $dispatcher->addListener('console.command', function ($event) {

            // Guard rediscovery when one command calls another
            if (self::$loaded) {
                return;
            }

            $input = $event->getInput();

            // Override with discovered or given config file
            self::$repository->merge(
                self::configFromCommand($input)
            );

            // Override with discovered or given config file
            self::$repository->merge([
                'ini' => self::iniFromCommand($input),
            ]);

            // Merge all command arguments into the config
            self::$repository->merge(
                array_filter($input->getArguments()),
            );

            self::$loaded = true;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    */

    /*
     * Will look for a config file in the following order
     *    1. Custom path specified via `--config=path/to/file.json`
     *    2. `phpacker.json` in the source directory via `--src` option
     *    3. `phpacker.json` in the current working directory
     *
     * This discovery priority logic does complicate things, but I
     * think the flexibility it grants outweighs the technical debt
     */
    private static function configFromCommand(InputInterface $input): array
    {
        $internalConfig = self::readJsonFile(self::INTERNAL_CONFIG);

        // 1. Custom path specified via `--config=path/to/file.json`
        $configPath = $input->hasOption('config')
            ? $input->getOption('config')
            : false;

        if (is_string($configPath)) {
            info("Using config file at '{$configPath}'");

            $config = array_merge($internalConfig, self::readJsonFile($configPath));

            return self::convertPaths($config, dirname($configPath));
        }

        // 2. `phpacker.json` in the source directory via `--src` option
        $sourceFile = $input->hasOption('src')
            ? $input->getOption('src')
            : false;

        if (is_string($sourceFile)) {
            // Determine the folder of $sourcePath
            $sourceDir = dirname($sourceFile);
            $configPath = Path::join($sourceDir, 'phpacker.json');

            // Use project config paths relative to itself
            if (file_exists($configPath)) {
                info("Using config file at '{$configPath}'");

                $config = array_merge($internalConfig, self::readJsonFile($configPath));

                return self::convertPaths($config, dirname($configPath));
            }

            // This is key, make sure the defaults are relative to the src,
            // So if we fall through below, that will be the default.
            $internalConfig = self::convertPaths($internalConfig, $sourceDir);
        }

        // 3. `phpacker.json` in the current working directory
        $configPath = Path::join(getcwd(), 'phpacker.json');

        if (file_exists($configPath)) {
            info("Using config file at '{$configPath}'");

            $config = array_merge($internalConfig, self::readJsonFile($configPath));

            return self::convertPaths($config, './');
        }

        return $internalConfig;
    }

    /*
     * Will look for ini configuration in the following order
     *    1. Custom path specified via `--ini=path/to/file.ini`
     *    2. Path specified in discovered config file
     *    3. `phpacker.ini` in the source directory via `--src` option
     *    4. `phpacker.ini` in the current working directory
     *    5. Interactive prompt if `--ini` is passed without a value (handled in build command)
     */
    private static function iniFromCommand(InputInterface $input): array
    {
        // 1. Custom path specified via `--ini=path/to/file.ini`
        $iniPath = $input->hasOption('ini')
            ? $input->getOption('ini')
            : false;

        if (is_string($iniPath)) {
            info("Using ini file at '{$iniPath}'");

            return self::readIniFile($iniPath);
        }

        // 2. Path specified in discovered config file
        $iniPath = self::$repository->get('ini');

        if (is_string($iniPath) && $iniPath != '') {
            info("Using ini file at '{$iniPath}'");

            return self::readIniFile($iniPath);
        }

        // 3. `phpacker.ini` in the source directory via `--src` option
        $sourceFile = $input->hasOption('src')
            ? $input->getOption('src')
            : false;

        if (is_string($sourceFile)) {
            // Determine the folder of $sourcePath
            $sourceDir = dirname($sourceFile);
            $iniPath = Path::join($sourceDir, 'phpacker.ini');

            // Use project config paths relative to itself
            if (file_exists($iniPath)) {
                info("Using ini file at '{$iniPath}'");

                return self::readIniFile($iniPath);
            }
        }

        // 4. `phpacker.ini` in the current working directory
        $iniPath = Path::join($iniPath, 'phpacker.ini');

        if (file_exists($iniPath)) {
            info("Using ini file at '{$iniPath}'");

            return self::readIniFile($iniPath);
        }

        // 5. Interactive prompt if `--ini` is passed without a value (handled in build command)
        return [];
    }

    private static function convertPaths(array $config, $basePath): array
    {
        $convert = [
            'src',
            'dest',
        ];

        foreach ($convert as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $config[$key] = Path::makeAbsolute($config[$key], realpath($basePath));
            }
        }

        return $config;
    }
}
