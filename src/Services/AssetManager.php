<?php

namespace PHPacker\PHPacker\Services;

use ZipArchive;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class AssetManager
{
    public function __construct(
        protected string $repositoryDir
    ) {
        $this->prepareDirectory();
    }

    public function prepareDirectory()
    {
        if (! is_dir($this->repositoryDir)) {
            mkdir($this->repositoryDir);
        }
    }

    public function currentVersion(): ?string
    {
        return @file_get_contents(Path::join($this->repositoryDir, '_version'));
    }

    public function setCurrentVersion(string $version)
    {
        file_put_contents(Path::join($this->repositoryDir, '_version'), $version);
    }

    public function unpack(string $zipPath, string $version)
    {
        $tempDir = Path::join($this->repositoryDir, uniqid('_unpack_'));

        if (! file_exists($zipPath)) {
            throw new FileNotFoundException("Zip not present in path '{$zipPath}'");
        }

        try {
            // Extract archive
            $this->unzip($zipPath, $tempDir);

            // Find all binaries in the $binDir
            $files = Finder::create()
                ->files()
                ->in($tempDir . '//*//bin')
                ->name('*.zip');

            if (! count($files)) {
                throw new FileNotFoundException("No PHP binaries found in downloaded release '{repository}/bin'");
            }

            // Extract them all
            foreach ($files as $zip) {
                $realPath = $zip->getRealPath();
                $destPath = Path::join($this->repositoryDir, substr($realPath, strrpos($realPath, 'bin') + 4));
                $this->unzip($realPath, $destPath);
            }

            $this->setCurrentVersion($version);

        } finally {
            // Cleanup
            $filesystem = new Filesystem;
            $filesystem->remove($zipPath);
            $filesystem->remove($tempDir);
        }
    }

    private function unzip($src, $dest)
    {
        $zip = new ZipArchive;

        if (! is_dir($dest)) {
            mkdir($dest, 0777, true);
        }

        if ($zip->open($src) === true) {
            $zip->extractTo($dest);
            $zip->close();
        } else {
            throw new RuntimeException("Unable to open zip file '{$src}'");
        }
    }
}
