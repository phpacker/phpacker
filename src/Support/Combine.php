<?php

namespace PHPacker\PHPacker\Support;

use Symfony\Component\Filesystem\Path;
use PHPacker\PHPacker\Command\Download;
use Symfony\Component\Filesystem\Filesystem;
use PHPacker\PHPacker\Support\Config\ConfigRepository;
use PHPacker\PHPacker\Exceptions\CommandErrorException;

class Combine
{
    const PLATFORM_MAP = [
        'mac' => 'mac',
        'linux' => 'linux',
        'windows' => 'win',
    ];

    const ARCH_MAP = [
        'arm' => 'arm64',
        'x64' => 'x64',
    ];

    public static function build(string $platform, string $arch, ConfigRepository $config)
    {
        $phpVersion = $config->get('php');
        $repository = $config->get('repository');
        $buildDirectory = $config->get('dest');

        $repositoryDir = $repository === Download::DEFAULT_REPOSITORY
            ? Download::DEFAULT_REPOSITORY_DIR
            : $repository;

        $repositoryDir = Path::join(APP_DATA, 'binaries', $repositoryDir);

        if (! file_exists($repositoryDir)) {
            throw new CommandErrorException("Repository {$repositoryDir} does not exit");
        }

        $binPath = Path::join($repositoryDir, self::PLATFORM_MAP[$platform], self::ARCH_MAP[$arch], (string) $phpVersion, 'micro.sfx');
        if (! file_exists($binPath)) {
            throw new CommandErrorException("PHP binary {$binPath} does not exit");
        }

        // print_r($config->all());

        // Combine the files
        $srcPath = Path::join($config->get('src'));
        if (! file_exists($srcPath)) {
            throw new CommandErrorException("Source at {$srcPath} does not exit");
        }

        $outputPath = Path::join($buildDirectory, $platform, "{$platform}-{$arch}");
        $iniPart = ''; // TODO Inject INI

        if ($platform === 'windows') {
            $outputPath .= '.exe';
        }

        // Make sure output path & file exist
        $filesystem = new Filesystem;
        $filesystem->mkdir(dirname($outputPath), 0755);
        touch($outputPath);
        chmod($outputPath, 0755); // chmod +x

        // Combine all data in the output path
        $combined = file_get_contents($binPath) . $iniPart . file_get_contents($srcPath);
        $result = file_put_contents($outputPath, $combined);

        if ($result === false) {
            throw new CommandErrorException('Build failed');
        }

        return true;
    }
}
