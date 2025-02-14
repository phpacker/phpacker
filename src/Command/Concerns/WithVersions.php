<?php

namespace PHPacker\PHPacker\Command\Concerns;

use Symfony\Component\Filesystem\Path;

// TODO: maybe put all interactions with the
trait WithVersions
{
    protected function currentVersion(string $repositoryDir): ?string
    {
        return @file_get_contents(Path::join($repositoryDir, 'version'));
    }

    protected function setCurrentVersion(string $repositoryDir, string $version)
    {
        file_put_contents(Path::join($repositoryDir, 'version'), $version);
    }
}
