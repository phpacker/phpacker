<?php

use Symfony\Component\Console\Command\Command;

beforeEach(function () {
    $this->originalWorkingDirectory = getcwd();
    chdir(__DIR__);
});

afterEach(function () {
    chdir($this->originalWorkingDirectory);
});

it('builds for all platforms', function () {
    $buildPath = '../_stubs/build';

    command('build', [
        'platform' => 'all',
        '--src' => '../_stubs/app.php',
        '--dest' => $buildPath,
        '--quiet',
    ])->getStatusCode()->toBe(Command::SUCCESS);

    expect([
        $buildPath . '/linux-arm/linux-arm',
        $buildPath . '/linux-x64/linux-x64',
        $buildPath . '/mac-arm/mac-arm',
        $buildPath . '/mac-x64/mac-x64',
        $buildPath . '/windows-x64/windows-x64.exe',
    ])->each->toBeFile();
});

it('supports custom executable names', function() {
    $buildPath = '../_stubs/build';

    command('build', [
        'platform' => 'all',
        '--src' => '../_stubs/app.php',
        '--dest' => $buildPath,
        '--filename' => 'foo-bar',
        '--quiet',
    ])->getStatusCode()->toBe(Command::SUCCESS);

    expect([
        $buildPath . '/linux-arm/foo-bar',
        $buildPath . '/linux-x64/foo-bar',
        $buildPath . '/mac-arm/foo-bar',
        $buildPath . '/mac-x64/foo-bar',
        $buildPath . '/windows-x64/foo-bar.exe',
    ])->each->toBeFile();
});

it('can run the executable', function () {
    $buildPath = '../_stubs/build';

    command('build', [
        'platform' => 'all',
        '--src' => '../_stubs/app.php',
        '--dest' => $buildPath,
        '--quiet',
    ])->getStatusCode()->toBe(Command::SUCCESS);

    $executable = [
        'mac-arm' => $buildPath . '/mac-arm/mac-arm',
        'mac-x64' => $buildPath . '/mac-x64/mac-x64',
        'linux-arm' => $buildPath . '/linux-arm/linux-arm',
        'linux-x64' => $buildPath . '/linux-x64/linux-x64',
        'windows-x64' => $buildPath . '/windows-x64/windows-x64.exe',
    ][getPlatform() . '-' . getArch()];

    shell($executable)
        ->isSuccessful()->toBeTrue()
        ->getOutput()->toContain('Hello World!');
});

it('injects ini', function () {
    $buildPath = '../_stubs/build';

    // Set these to some unlikely defaults
    $this->filesystem->dumpFile('artifacts/phpacker.ini', <<< 'TXT'
        memory_limit=16M
        max_execution_time=999
    TXT);

    command('build', [
        'platform' => 'all',
        '--dest' => $buildPath,
        '--src' => '../_stubs/app.php',
        '--ini' => 'artifacts/phpacker.ini',
        '--quiet',
    ])->getStatusCode()->toBe(Command::SUCCESS);

    $executable = [
        'mac-arm' => $buildPath . '/mac-arm/mac-arm',
        'mac-x64' => $buildPath . '/mac-x64/mac-x64',
        'linux-arm' => $buildPath . '/linux-arm/linux-arm',
        'linux-x64' => $buildPath . '/linux-x64/linux-x64',
        'windows-x64' => $buildPath . '/windows-x64/windows-x64.exe',
    ][getPlatform() . '-' . getArch()];

    shell($executable)
        ->isSuccessful()->toBeTrue()
        ->getOutput()->toContain('memory_limit: 16M')
        ->getOutput()->toContain('max_execution_time: 999');
});
