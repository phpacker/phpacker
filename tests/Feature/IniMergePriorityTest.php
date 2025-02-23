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

it('normalizes ini config to a array', function () {

    $defaultConfig = json_decode(file_get_contents('../../config/phpacker.json'));

    commandDouble()
        ->exit_code->toBe(Command::SUCCESS);

    expect($defaultConfig)
        ->ini->not->toBeTrue();

    expect(ConfigManager::all())
        ->ini->toBeArray();
});

it('discovers ini in --src path', function () {
    $this->filesystem->dumpFile('artifacts/phpacker.ini', <<< 'TXT'
        FOO=BAR
        BAZ=ZAH
    TXT);

    commandDouble([
        '--src' => 'artifacts/app.php',
    ])->exit_code->toBe(Command::SUCCESS);
    // ->output->toContain("Using ini file at 'artifacts/phpacker.ini'");

    expect(ConfigManager::get('ini'))
        ->FOO->toBe('BAR')
        ->BAZ->toBe('ZAH');
});

it('uses ini with --ini option', function () {
    $this->filesystem->dumpFile('artifacts/path-to/custom.ini', <<< 'TXT'
        FAS=BAS
        BAZ=NAH
    TXT);

    commandDouble([
        '--ini' => 'artifacts/path-to/custom.ini',
    ])->exit_code->toBe(Command::SUCCESS);
    // ->output->toContain("Using ini file at 'artifacts/phpacker.ini'");

    expect(ConfigManager::get('ini'))
        ->FAS->toBe('BAS')
        ->BAZ->toBe('NAH');
});

it('uses ini in loaded config with --config option', function () {

    $this->filesystem->dumpFile('artifacts/path-to/ini/custom.ini', <<< 'TXT'
        FAR=BAK
        BAW=NAZ
    TXT);

    $this->filesystem->dumpFile('artifacts/path-to/config.json', json_encode([
        'ini' => 'ini/custom.ini',
    ]));

    commandDouble([
        '--config' => 'artifacts/path-to/config.json',
    ])->exit_code->toBe(Command::SUCCESS);
    // ->output->toContain("Using ini file at '/artifacts/path-to/custom.ini'");

    expect(ConfigManager::get('ini'))
        ->FAR->toBe('BAK')
        ->BAW->toBe('NAZ');
});

it('uses ini in cwd', function () {
    $this->filesystem->dumpFile('artifacts/cwd/phpacker.ini', <<< 'TXT'
        FUG=GME
        LIG=MAH
    TXT);

    // Change cwd
    chdir('./artifacts/cwd');

    // Execute command
    commandDouble()->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::get('ini'))
        ->FUG->toBe('GME')
        ->LIG->toBe('MAH');
});

it('gives ini precedence to --ini over --src', function () {

    $this->filesystem->dumpFile('artifacts/phpacker.ini', <<< 'TXT'
        FROM=DISCOVERED_IN_SRC
    TXT);

    $this->filesystem->dumpFile('artifacts/path-to/custom.ini', <<< 'TXT'
        FROM=INI_OPTION
    TXT);

    commandDouble([
        '--src' => 'artifacts/app.php',
        '--ini' => 'artifacts/path-to/custom.ini',
    ])->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::get('ini'))
        ->FROM->toBe('INI_OPTION');
});

it('gives ini precedence to --ini over --config', function () {

    $this->filesystem->dumpFile('artifacts/path-to/config.json', json_encode([
        'ini' => 'config/custom.ini',
    ]));

    $this->filesystem->dumpFile('artifacts/path-to/config/custom.ini', <<< 'TXT'
        FROM=CONFIG_OPTION
    TXT);

    $this->filesystem->dumpFile('artifacts/ini-option/custom.ini', <<< 'TXT'
        FROM=INI_OPTION
    TXT);

    commandDouble([
        '--config' => 'artifacts/path-to/config.json',
        '--ini' => 'artifacts/ini-option/custom.ini',
    ])->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::get('ini'))
        ->FROM->toBe('INI_OPTION');
});

it('gives ini precedence to --ini over cwd', function () {

    $this->filesystem->dumpFile('artifacts/cwd/phpacker.ini', <<< 'TXT'
        FROM=CWD
    TXT);

    // Change cwd
    chdir('./artifacts/cwd');

    $this->filesystem->dumpFile('artifacts/ini-option/custom.ini', <<< 'TXT'
        FROM=INI_OPTION
    TXT);

    commandDouble([
        '--ini' => 'artifacts/ini-option/custom.ini',
    ])->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::get('ini'))
        ->FROM->toBe('INI_OPTION');
});

it('gives ini precedence to --config over --src', function () {
    $this->filesystem->dumpFile('artifacts/path-to/config.json', json_encode([
        'ini' => 'config/custom.ini',
    ]));

    $this->filesystem->dumpFile('artifacts/path-to/config/custom.ini', <<< 'TXT'
        FROM=CONFIG_OPTION
    TXT);

    $this->filesystem->dumpFile('artifacts/src-option/phpacker.ini', <<< 'TXT'
        FROM=SRC_OPTION
    TXT);

    commandDouble([
        '--config' => 'artifacts/path-to/config.json',
        '--src' => 'artifacts/src-option/phpacker.ini',
    ])->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::get('ini'))
        ->FROM->toBe('CONFIG_OPTION');
});

it('gives ini precedence to --config over cwd', function () {

    $this->filesystem->dumpFile('artifacts/path-to/config.json', json_encode([
        'ini' => 'config/custom.ini',
    ]));

    $this->filesystem->dumpFile('artifacts/path-to/config/custom.ini', <<< 'TXT'
        FROM=CONFIG_OPTION
    TXT);

    $this->filesystem->dumpFile('artifacts/cwd/phpacker.ini', <<< 'TXT'
        FROM=CWD
    TXT);

    // Change cwd
    chdir('./artifacts/cwd');

    commandDouble([
        '--config' => '../path-to/config.json',
    ])->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::get('ini'))
        ->FROM->toBe('CONFIG_OPTION');
});

it('gives ini precedence to --src over cwd', function () {

    $this->filesystem->dumpFile('artifacts/src-option/phpacker.ini', <<< 'TXT'
        FROM=SRC_OPTION
    TXT);

    $this->filesystem->dumpFile('artifacts/cwd/phpacker.ini', <<< 'TXT'
        FROM=CWD
    TXT);

    // Change cwd
    chdir('./artifacts/cwd');

    commandDouble([
        '--src' => '../src-option/phpacker.ini',
    ])->exit_code->toBe(Command::SUCCESS);

    expect(ConfigManager::get('ini'))
        ->FROM->toBe('SRC_OPTION');
});
