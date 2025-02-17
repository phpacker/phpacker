<?php

namespace PHPacker\PHPacker\Command\Concerns;

use PHPacker\PHPacker\Exceptions\CommandErrorException;

trait InteractsWithFiles
{
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
}
