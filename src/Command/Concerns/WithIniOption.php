<?php

namespace PHPacker\PHPacker\Command\Concerns;

use Symfony\Component\Console\Input\InputInterface;
use PHPacker\PHPacker\Exceptions\CommandErrorException;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\textarea;

trait WithIniOption
{
    /*
     * When the --ini option is given without input, it'll look for a ./phpacker.ini file
     * If not found it'll prompt with a multiline input. You may also pass a file path
     */
    protected function determineIni(InputInterface $input)
    {
        $iniInput = $input->getOption('ini');

        if (gettype($iniInput) === 'string') {
            if (! file_exists($iniInput)) {
                throw new CommandErrorException("No ini file found at '{$iniInput}'");
            }

            info('Using ./' . basename($iniInput));

            return $this->parseIni(file_get_contents($iniInput));
        }

        // CWD phpacker.ini
        $defaultIniPath = getcwd() . '/phpacker.ini';
        if (file_exists($defaultIniPath)) {
            info('Using ./' . basename($defaultIniPath));

            return $this->parseIni(file_get_contents($defaultIniPath));
        }

        $manualInput = textarea(
            'ini settings',
            required: true
        );

        return $this->parseIni($manualInput);
    }

    protected function parseIni(string $raw)
    {
        $ini = parse_ini_string($raw, scanner_mode: INI_SCANNER_RAW);

        if ($ini === false) {
            throw new CommandErrorException('Invalid ini input. Please check for syntax errors');
        }

        if (empty($ini)) {
            throw new CommandErrorException('No values found for --ini');
        }

        return $ini;
    }

    protected function printIniTable(array $ini)
    {
        $rows = array_map(function ($value, $key) {
            return [$key, $value];
        }, $ini, array_keys($ini));

        table(['  directive  ', '    value    '], $rows);
    }
}
