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
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    */

    /*
     * When a --config option was given, use it
     * Otherwise scan the src argument directory
     */
    private static function configFromCommand(InputInterface $input): array
    {
        if (! $input->hasOption('config') && ! $input->hasOption('src')) {
            return [];
        }

        // If a --config option was given
        $configPath = $input->getOption('config');

        if (is_string($configPath)) {
            info("Using config file at '{$configPath}'");

            return self::convertPaths(
                self::readJsonFile($configPath),
                dirname($configPath)
            );
        }

        // If a --src option was given scan the src dir
        $sourceFile = $input->getOption('src');

        if (is_string($sourceFile)) {
            // Determine the folder of $sourcePath
            $sourceDir = dirname($sourceFile);
            $projectConfig = Path::join($sourceDir, 'phpacker.json');

            // Use project config paths relative to itself
            if (file_exists($projectConfig)) {
                info("Using config file at '{$projectConfig}'");

                return self::convertPaths(
                    self::readJsonFile($projectConfig),
                    dirname($projectConfig)
                );
            }

            // Use default config paths relative to --src
            return self::convertPaths(
                self::readJsonFile(self::INTERNAL_CONFIG),
                $sourceDir
            );
        }

        return [];
    }

    /*
     * When a --ini option was given, use it
     * Otherwise scan the src argument directory
     */
    private static function iniFromCommand(InputInterface $input): array
    {
        // No --ini configured in command
        if (! $input->hasOption('ini')) {
            return [];
        }

        // If a --ini option was given
        $iniPath = $input->getOption('ini');

        if (is_string($iniPath)) {
            info("Using ini file at '{$iniPath}'");

            return self::readIniFile($iniPath);
        }

        // If a --src option was given scan the src dir
        $sourceFile = $input->getOption('src');

        if (is_string($sourceFile)) {
            // Determine the folder of $sourcePath
            $sourceDir = dirname($sourceFile);
            $projectIni = Path::join($sourceDir, 'phpacker.ini');

            if (file_exists($projectIni)) {
                info("Using ini file at '{$projectIni}'");

                return self::readIniFile($projectIni);
            }
        }

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
