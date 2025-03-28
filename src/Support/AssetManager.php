<?php

namespace PHPacker\PHPacker\Support;

use ZipArchive;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class AssetManager
{
    const DEFAULT_REPOSITORY = 'phpacker/php-bin';
    const DEFAULT_REPOSITORY_DIR = 'default';

    protected string $repository;
    protected string $repositoryDir;

    public function __construct(
        string $repository
    ) {
        $dirName = $repository === self::DEFAULT_REPOSITORY
            ? self::DEFAULT_REPOSITORY_DIR
            : str_replace(['/', '\\'], '-', $repository);

        $this->repository = $repository;
        $this->repositoryDir = Path::join(APP_DATA, 'binaries', $dirName);

        $this->prepareDirectory();
    }

    public function currentVersion(): ?string
    {
        return @file_get_contents(Path::join($this->repositoryDir, '_version'));
    }

    public function setCurrentVersion(string $version)
    {
        file_put_contents(Path::join($this->repositoryDir, '_version'), $version);
    }

    public function baseDir(): ?string
    {
        return $this->repositoryDir;
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

            // Check if the bin directory exists
            $binDir = glob($tempDir . '//*//bin', GLOB_ONLYDIR);
            if (empty($binDir)) {
                $this->purgeRepositoryDirIfNew();
                throw new FileNotFoundException('Bin directory not found in unpacked archive');
            }

            // Find all binaries in the $binDir
            $files = Finder::create()
                ->files()
                ->in($tempDir . '//*//bin')
                ->name('*.zip');

            if (! count($files)) {
                $this->purgeRepositoryDirIfNew();
                throw new FileNotFoundException("No PHP binaries found in downloaded release '{repository}/bin'");
            }

            // Extract them all
            foreach ($files as $zip) {
                $realPath = $zip->getRealPath();

                // Work out the unpack destination
                $destPath = substr($realPath, strrpos($realPath, 'bin') + 4);
                $destPath = Path::join($this->repositoryDir, $destPath);
                $destPath = preg_replace('/php-(\d+\.\d+)\.zip$/', '$1', $destPath);

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

    public function prepareDirectory()
    {
        if (! is_dir($this->repositoryDir)) {
            mkdir($this->repositoryDir, 0755, recursive: true);
        }
    }

    public function clearDirectory()
    {
        $filesystem = new Filesystem;
        $filesystem->remove($this->repositoryDir);
    }

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    */
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

    /* Ensure no download artifacts linger after a failed initial install */
    private function purgeRepositoryDirIfNew()
    {
        if (! $this->currentVersion()) {
            $filesystem = new Filesystem;
            $filesystem->remove($this->repositoryDir);
        }
    }
}
