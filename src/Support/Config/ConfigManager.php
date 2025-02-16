<?php

namespace PHPacker\PHPacker\Support\Config;

use BadMethodCallException;
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
            self::readFile(self::INTERNAL_CONFIG)
        );

        // Dynamically merge config based on command input
        $dispatcher->addListener('console.command', function ($event) {

            // If a --config option was given, use that file
            // If none was found and.. a --src option was given, search for a phpacker.json and merge

            // If a --ini option was given, use that file (only if input is string)
            // If none was found and.. a --src option was given, search for a phpacker.ini and merge

            // NOTE: all other arguments and options need to be manually set from the command input

            // self::$repository->merge($inputAsArray);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    */
    private static function readFile($path): array
    {
        if (! file_exists($path)) {
            throw new CommandErrorException("File not found: {$path}");
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
}
