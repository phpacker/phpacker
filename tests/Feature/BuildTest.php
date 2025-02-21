<?php

use Symfony\Component\Console\Command\Command;

it('builds for all platforms', function () {
    $buildPath = __DIR__ . '/../_stubs/build';

    command('build', [
        'platform' => 'all',
        '--src' => __DIR__ . '/../_stubs/app.php',
        '--dest' => $buildPath,
        '--quiet',
    ])->getStatusCode()->toBe(Command::SUCCESS);

    expect([
        $buildPath . '/linux/linux-arm',
        $buildPath . '/linux/linux-x64',
        $buildPath . '/mac/mac-arm',
        $buildPath . '/mac/mac-x64',
        $buildPath . '/windows/windows-x64.exe',
    ])->each->toBeFile();
});

it('can run the executable', function () {
    $buildPath = __DIR__ . '/../_stubs/build';

    command('build', [
        'platform' => 'all',
        '--src' => __DIR__ . '/../_stubs/app.php',
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
        ->getOutput()->toContain('Hello World!');
});
