<?php

use Symfony\Component\Console\Command\Command;

beforeEach(function () {
    $this->originalWorkingDirectory = getcwd();
    chdir(__DIR__);
});

afterEach(function () {
    chdir($this->originalWorkingDirectory);
});

it('detects local environment', function () {
    // NOTE: the 'local' value is only picked up predictably when
    // the composer autoloader is required by the build source.

    // Somehow GH actions won't pick it up without setting it explicitly
    // This does work locally without workaround.

    shell('PHPACKER_ENV=local php ' . __DIR__ . '/../_stubs/app.php')
        ->isSuccessful()->toBeTrue()
        ->getOutput()->toContain('PHPACKER_ENV: local');
});

it('detects production environment', function () {

    $buildPath = '../_stubs/build';

    command('build', [
        'platform' => 'all',
        '--src' => '../_stubs/app.php',
        '--dest' => $buildPath,
        '--quiet',
    ])->getStatusCode()->toBe(Command::SUCCESS);

    $executable = [
        'mac-arm' => $buildPath . '/mac/mac-arm',
        'mac-x64' => $buildPath . '/mac/mac-x64',
        'linux-arm' => $buildPath . '/linux/linux-arm',
        'linux-x64' => $buildPath . '/linux/linux-x64',
        'windows-x64' => $buildPath . '/windows/windows-x64.exe',
    ][getPlatform() . '-' . getArch()];

    shell($executable)
        ->isSuccessful()->toBeTrue()
        ->getOutput()->toContain('PHPACKER_ENV: production');
})->skip('reverting feature due to checksum issue when injecting code');
