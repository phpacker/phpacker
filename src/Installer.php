<?php

namespace PHPacker\PHPacker;

use Symfony\Component\Finder\Finder;
use PHPacker\PHPacker\Command\Concerns\InteractsWithFiles;

class Installer
{
    use InteractsWithFiles;

    public static function hook()
    {
        $configPath = self::findConfig();

        if (! $configPath) {
            return; // TODO: Display some warning?
        }

        $config = self::readJsonFile($configPath);

        print_r($config);
    }

    private static function findConfig(): ?string
    {
        $finder = new Finder;
        $finder->files()
            ->in(dirname(__DIR__, 3))
            ->exclude(['vendor', 'tests'])
            ->name('phpacker.json')
            ->depth('<= 3');

        foreach ($finder as $file) {
            return $file->getRealPath();
        }

        return null;
    }
}
