<?php

use Symfony\Component\Console\Command\Command;
use PHPacker\PHPacker\Support\Config\ConfigManager;

/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
*/

it('uses default config when nothing is discovered', function () {

    $defaultConfig = json_decode(file_get_contents(__DIR__ . '/../../config/phpacker.json'));
    $defaultConfig->ini = []; // Normalized by the config manager
    $defaultConfig->command = 'stub'; // Injected by the config manager

    commandDouble()
        ->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::all())
        ->toEqual($defaultConfig);
});

it('discovers config in src path', function () {
    commandDouble([
        //
    ])->exit_code->toBe(Command::SUCCESS);

    print_r(ConfigManager::all());
});

it('uses config with --config option');

it('uses config in cwd');

it('gives precedence to --config over --src');

it('gives precedence to --config over cwd');

it('gives precedence to --src over cwd');

it('loads src from config');

/*
|--------------------------------------------------------------------------
| INI
|--------------------------------------------------------------------------
*/
it('normalizes ini config to a array', function () {

    $defaultConfig = json_decode(file_get_contents(__DIR__ . '/../../config/phpacker.json'));

    commandDouble()
        ->exit_code->toBe(Command::SUCCESS);

    expect($defaultConfig)
        ->ini->not->toBeTrue();

    expect(ConfigManager::all())
        ->ini->toBeArray();
});

it('discovers ini in --src path');

it('uses ini with --ini option');

it('uses ini in loaded config with --config option');

it('uses ini in cwd');

it('gives precedence to --ini over --src');

it('gives precedence to --ini over --config');

it('gives precedence to --ini over cwd');

it('gives precedence to --config over --src');

it('gives precedence to --config over cwd');

it('gives precedence to --src over cwd');
