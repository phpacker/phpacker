<?php

namespace PHPacker\PHPacker\Command\Concerns;

use Symfony\Component\Filesystem\Path;

trait WithVersions
{
    use InteractsWithRepository;

    protected function currentVersion(string $repositoryDir): ?string
    {
        return @file_get_contents(Path::join($repositoryDir, 'version'));
    }

    protected function latestVersion(): ?string
    {
        $response = @$this->repository()->releaseData();

        return $response['tag_name'] ?? null;
    }

    protected function setCurrentVersion(string $repositoryDir, string $version)
    {
        file_put_contents(Path::join($repositoryDir, 'version'), $version);
    }
}
