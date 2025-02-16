<?php

namespace PHPacker\PHPacker\Support\Config;

use PHPacker\PHPacker\Exceptions\CommandErrorException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigManager
{
    const INTERNAL_CONFIG = __DIR__ . '/../../../config/phpacker.json';

    protected static ConfigRepository $repository;

    public static function bootstrap(EventDispatcherInterface $dispatcher)
    {
        // Init static config repository
        self::$repository = new ConfigRepository(
            self::readFile(self::INTERNAL_CONFIG)
        );

        // Make sure console input is always merged
        $dispatcher->addListener('console.command', function ($event) {
            $arguments = $event->getInput()->getArguments();
            $options = $event->getInput()->getOptions();

            // TODO: merge config file found at src-root (or --config option if present)
            $inputAsArray = array_filter(array_merge($arguments, $options));

            print_r($inputAsArray);

            // Merge all args & options
            self::$repository->merge($inputAsArray);
        });
    }

    public static function readFile($path): array
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
