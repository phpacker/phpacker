<?php

namespace PHPacker\PHPacker\Command\Concerns;

use Symfony\Component\Filesystem\Path;

trait WithVersions
{
    use InteractsWithGitHub;

    protected function currentVersion(string $repositoryDir): ?string
    {
        return @file_get_contents(Path::join($repositoryDir, 'version'));
    }

    protected function latestVersion(string $repository): ?string
    {
        $response = @$this->releaseData($repository);

        return $response['tag_name'] ?? null;
    }

    protected function setLatestVersion(string $repositoryDir, string $version)
    {
        file_put_contents(Path::join($repositoryDir, 'version'), $version);
    }
}
