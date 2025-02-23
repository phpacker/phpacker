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
    $this->filesystem->dumpFile(__DIR__ . '/artifacts/phpacker.json', json_encode([
        'dest' => 'discovered',
    ]));

    commandDouble([
        '--src' => __DIR__ . '/artifacts/app.php',
    ])->exit_code->toBe(Command::SUCCESS);
    // ->output->toContain("Using config file at 'artifacts/phpacker.json'");

    // The paths will be converted to be relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/discovered');

});

it('uses config with --config option', function () {
    // print_r(ConfigManager::all());
    $this->filesystem->dumpFile(__DIR__ . '/artifacts/nested/config.json', json_encode([
        'dest' => 'foo/discovered-via-config-option',
    ]));

    commandDouble([
        '--config' => __DIR__ . '/artifacts/nested/config.json',
    ])->exit_code->toBe(Command::SUCCESS);

    // The paths will be converted to be relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/nested/foo/discovered-via-config-option');
});

it('uses config in cwd', function () {
    $this->filesystem->dumpFile(__DIR__ . '/artifacts/cwd/phpacker.json', json_encode([
        'dest' => 'bar/discovered-in-cwd',
    ]));

    // Change cwd
    chdir(__DIR__ . '/artifacts/cwd');

    // Execute command
    commandDouble()->exit_code->toBe(Command::SUCCESS);

    // The paths will be converted to be relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(getcwd() . '/bar/discovered-in-cwd');

});

it('gives config precedence to --config over --src');

it('gives config precedence to --config over cwd');

it('gives config precedence to --src over cwd');

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

it('gives ini precedence to --ini over --src');

it('gives ini precedence to --ini over --config');

it('gives ini precedence to --ini over cwd');

it('gives ini precedence to --config over --src');

it('gives ini precedence to --config over cwd');

it('gives ini precedence to --src over cwd');
