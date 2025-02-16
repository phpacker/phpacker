<?php

namespace PHPacker\PHPacker\Support\Config;

use BadMethodCallException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
            self::readJsonFile(self::INTERNAL_CONFIG)
        );

        // Dynamically merge config based on command input
        $dispatcher->addListener('console.command', function ($event) {
            $input = $event->getInput();

            self::$repository->merge(
                self::configFromCommand($input)
            );

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

    /*
     * When a --config option was given, use it
     * Otherwise scan the src argument directory
     */
    private static function configFromCommand(InputInterface $input): array
    {

        // If a --config option was given
        $configPath = $input->getOption('config');

        if (is_string($configPath)) {
            return self::readJsonFile($configPath);
        }

        // If a --src option was given scan the src dir
        $sourceFile = $input->getOption('src');

        if (is_string($sourceFile)) {
            // Determine the folder of $sourcePath
            $sourceDir = dirname($sourceFile);
            $projectConfig = Path::join($sourceDir, 'phpacker.json');

            if (file_exists($projectConfig)) {
                return self::readJsonFile($projectConfig);
            }
        }

        return [];
    }
}
