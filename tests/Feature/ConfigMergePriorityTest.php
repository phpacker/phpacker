<?php

use Symfony\Component\Console\Command\Command;
use PHPacker\PHPacker\Support\Config\ConfigManager;

beforeEach(function () {
    $this->originalWorkingDirectory = getcwd();
    chdir(__DIR__);
});

afterEach(function () {
    chdir($this->originalWorkingDirectory);
});

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
    $this->filesystem->dumpFile('artifacts/phpacker.json', json_encode([
        'dest' => 'discovered',
    ]));

    commandDouble(['--src' => 'artifacts/app.php'])
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at 'artifacts/phpacker.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/discovered');

});

it('uses config with --config option', function () {
    // print_r(ConfigManager::all());
    $this->filesystem->dumpFile('artifacts/nested/config.json', json_encode([
        'dest' => 'foo/discovered-via-config-option',
    ]));

    commandDouble(['--config' => 'artifacts/nested/config.json'])
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at 'artifacts/nested/config.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/nested/foo/discovered-via-config-option');
});

it('uses config in cwd', function () {
    $this->filesystem->dumpFile('artifacts/cwd/phpacker.json', json_encode([
        'dest' => 'bar/discovered-in-cwd',
    ]));

    // Change cwd
    chdir('./artifacts/cwd');

    // Execute command
    commandDouble()
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at '" . getcwd() . "/phpacker.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/cwd/bar/discovered-in-cwd');

});

it('gives config precedence to --config over --src', function () {
    $this->filesystem->dumpFile('artifacts/src/phpacker.json', json_encode([
        'dest' => 'discovered-via-src-path',
    ]));

    $this->filesystem->dumpFile('artifacts/nested/config.json', json_encode([
        'dest' => 'foo/discovered-via-config-option',
    ]));

    commandDouble([
        '--src' => 'artifacts/src/app.php',
        '--config' => 'artifacts/nested/config.json',
    ])
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at 'artifacts/nested/config.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/nested/foo/discovered-via-config-option');
});

it('gives config precedence to --config over cwd', function () {

    $this->filesystem->dumpFile('artifacts/cwd/phpacker.json', json_encode([
        'dest' => 'bar/discovered-in-cwd',
    ]));

    $this->filesystem->dumpFile('artifacts/nested/config.json', json_encode([
        'dest' => 'foo/discovered-via-config-option',
    ]));

    // Change cwd
    chdir('./artifacts/cwd');

    commandDouble([
        '--config' => '../nested/config.json',
    ])
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at '../nested/config.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/nested/foo/discovered-via-config-option');
});

it('gives config precedence to --src over cwd', function () {

    $this->filesystem->dumpFile('artifacts/cwd/phpacker.json', json_encode([
        'dest' => 'bar/discovered-in-cwd',
    ]));

    $this->filesystem->dumpFile('artifacts/cwd/config-option/phpacker.json', json_encode([
        'dest' => 'foo/discovered-via-src-path',
    ]));

    // Change cwd
    chdir('./artifacts/cwd');

    commandDouble([
        '--src' => 'config-option/app.php',
    ])
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at 'config-option/phpacker.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->dest->toBe(__DIR__ . '/artifacts/cwd/config-option/foo/discovered-via-src-path');
});

it('loads src from config', function () {

    // print_r(ConfigManager::all());
    $this->filesystem->dumpFile('artifacts/nested/config.json', json_encode([
        'src' => './path-to/app.php',
    ]));

    commandDouble([
        '--config' => 'artifacts/nested/config.json',
    ])
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at 'artifacts/nested/config.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->src->toBe(__DIR__ . '/artifacts/nested/path-to/app.php');
});

it('can use paths above config', function () {

    // print_r(ConfigManager::all());
    $this->filesystem->dumpFile('artifacts/nested/two/config.json', json_encode([
        'src' => './../../path-to/app.php',
    ]));

    commandDouble([
        '--config' => 'artifacts/nested/two/config.json',
    ])
        ->exit_code->toBe(Command::SUCCESS)
        ->output->toContain("Using config file at 'artifacts/nested/two/config.json'");

    // The paths will be converted to be absolute path relative to the file they came from
    expect(ConfigManager::all())
        ->src->toBe(__DIR__ . '/artifacts/path-to/app.php');
});
