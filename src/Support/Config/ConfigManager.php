<?php

namespace PHPacker\PHPacker\Support\Config;

use BadMethodCallException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function Laravel\Prompts\info;

/**
 * Class ConfigManager
 *
 * @method static mixed get(string $key): mixed
 * @method static object all(): array
 * @method static object merge(array $data)
 */
class ConfigManager
{
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

            self::$repository->merge(
                self::configFromCommand($input)
            );

            self::$repository->merge([
                'ini' => self::iniFromCommand($input),
            ]);

            // IMPORTANT
            // All other arguments and options need to be manually set from the command input
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    */
    private static function readJsonFile($path): array
    {
        if (! file_exists($path)) {
            throw new CommandErrorException("File not found: {$path}");
        }

        if (pathinfo($path, PATHINFO_EXTENSION) !== 'json') {
            throw new CommandErrorException("Invalid file type: {$path}. Expected a JSON file.");
        }

        $jsonData = file_get_contents($path);
        if ($jsonData === false) {
            throw new CommandErrorException("Failed to read file: {$path}");
        }

        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CommandErrorException('Config decode error: ' . json_last_error_msg());
        }

        return $data;
    }

    private static function readIniFile($path): array
    {
        if (! file_exists($path)) {
            throw new CommandErrorException("File not found: {$path}");
        }

        if (pathinfo($path, PATHINFO_EXTENSION) !== 'ini') {
            throw new CommandErrorException("Invalid file type: {$path}. Expected a INI file.");
        }

        $ini = parse_ini_string(file_get_contents($path), scanner_mode: INI_SCANNER_RAW);
        if ($ini === false) {
            throw new CommandErrorException('Invalid ini input. Please check for syntax errors');
        }

        if (empty($ini)) {
            throw new CommandErrorException("No INI definitions found in {$path}");
        }

        return $ini;
    }

    /*
     * When a --config option was given, use it
     * Otherwise scan the src argument directory
     */
    private static function configFromCommand(InputInterface $input): array
    {
        // No --config configured in command
        if (! $input->hasOption('config')) {
            return [];
        }

        // If a --config option was given
        $configPath = $input->getOption('config');

        if (is_string($configPath)) {
            info("Using config file at '{$configPath}'");

            return self::readJsonFile($configPath);
        }

        // If a --src option was given scan the src dir
        $sourceFile = $input->getOption('src');

        if (is_string($sourceFile)) {
            // Determine the folder of $sourcePath
            $sourceDir = dirname($sourceFile);
            $projectConfig = Path::join($sourceDir, 'phpacker.json');

            if (file_exists($projectConfig)) {
                info("Using config file at '{$projectConfig}'");

                return self::readJsonFile($projectConfig);
            }
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
}
