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

        // Check src path
        $srcPath = Path::join($config->get('src'));
        if (! file_exists($srcPath)) {
            throw new CommandErrorException("Source at {$srcPath} does not exit");
        }

        // Make sure output path & file exist
        $outputPath = Path::join($buildDirectory, $platform, "{$platform}-{$arch}");

        if ($platform === 'windows') {
            $outputPath .= '.exe';
        }

        $filesystem = new Filesystem;
        $filesystem->mkdir(dirname($outputPath), 0755);
        touch($outputPath);
        chmod($outputPath, 0755); // chmod +x

        // Get encoded INI definitions
        $iniPart = self::encodeINI($config->get('ini'));

        // Combine all data in the output path
        $combined = file_get_contents($binPath) . $iniPart . file_get_contents($srcPath);
        $result = file_put_contents($outputPath, $combined);

        if ($result === false) {
            throw new CommandErrorException('Build failed');
        }

        return true;
    }

    private static function encodeINI(array $ini = []): string
    {
        if (empty($ini)) {
            return '';
        }

        // Encode the INI definitions
        $formatted = [];
        foreach ($ini as $key => $val) {
            if (is_array($val)) {
                $formatted[] = "[{$key}]";
                foreach ($val as $skey => $sval) {
                    $formatted[] = "{$skey}=" . (is_numeric($sval) ? $sval : '"' . $sval . '"');
                }
            } else {
                $formatted[] = "{$key}=" . (is_numeric($val) ? $val : '"' . $val . '"');
            }
        }

        // Pack the definitions in a binary string
        $iniString = implode("\n", $formatted);
        $iniPart = "\xfd\xf6\x69\xe6";
        $iniPart .= pack('N', strlen($iniString));
        $iniPart .= $iniString;

        return $iniPart;
    }
}
